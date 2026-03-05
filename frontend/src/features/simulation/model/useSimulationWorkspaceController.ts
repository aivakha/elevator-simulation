import { useEffect, useMemo, useState } from 'react';
import {
  createSimulation,
  deleteSimulation,
  enqueueManualCall,
  fetchSimulationConfigOptions,
  lifecycleAction,
  listSimulations,
  setElevatorCondition,
  setSimulationCondition,
  updateSimulationConfig,
} from '../api/simulationApi';
import { buildPreviewViewModel } from './previewViewModel';
import type {
  CreateSimulationInput,
  SimulationConfigOptions,
  SimulationSummary,
} from './types';
import { useQueuePreview } from './useQueuePreview';

type ScreenMode = 'lobby' | 'simulation';

export function useSimulationWorkspaceController() {
  const [screenMode, setScreenMode] = useState<ScreenMode>('lobby');
  const [simulations, setSimulations] = useState<SimulationSummary[]>([]);
  const [configOptions, setConfigOptions] = useState<SimulationConfigOptions | null>(null);
  const [activeSimulationId, setActiveSimulationId] = useState<string | null>(null);
  const [selectedElevatorId, setSelectedElevatorId] = useState('');
  const [isBusy, setIsBusy] = useState(false);

  const { preview, isLive } = useQueuePreview(activeSimulationId ?? '');

  const activeSimulation = useMemo(
    () => simulations.find((item) => item.id === activeSimulationId) ?? null,
    [simulations, activeSimulationId],
  );

  const mode = activeSimulation?.config_json.mode ?? 'regular';
  const algorithm = activeSimulation?.config_json.algorithm ?? 'nearestCar';
  const isSimulationRunning = activeSimulation?.status === 'running';
  const visualizerSnapKey = `${activeSimulationId ?? 'none'}:${preview.tickNumber === 0 ? 'reset' : 'live'}`;

  const previewViewModel = useMemo(
    () => buildPreviewViewModel(preview, selectedElevatorId),
    [preview, selectedElevatorId],
  );

  const refreshSimulationList = async () => {
    const items = await listSimulations();
    setSimulations(items);
  };

  const withBusy = async <T>(task: () => Promise<T>): Promise<T> => {
    setIsBusy(true);

    try {
      return await task();
    } finally {
      setIsBusy(false);
    }
  };

  const runForActiveSimulation = async <T>(
    task: (simulationId: string) => Promise<T>,
    refreshList = false,
  ): Promise<T | null> => {
    if (!activeSimulationId) {
      return null;
    }

    return withBusy(async () => {
      const result = await task(activeSimulationId);

      if (refreshList) {
        await refreshSimulationList();
      }

      return result;
    });
  };

  const runForRunningSimulation = async <T>(
    task: (simulationId: string) => Promise<T>,
    refreshList = false,
  ): Promise<T | null> => {
    if (!isSimulationRunning) {
      return null;
    }

    return runForActiveSimulation(task, refreshList);
  };

  useEffect(() => {
    void refreshSimulationList();
    void fetchSimulationConfigOptions().then(setConfigOptions).catch(() => {});
  }, []);

  useEffect(() => {
    if (preview.elevators.length === 0) {
      setSelectedElevatorId('');
      return;
    }

    if (!preview.elevators.some((item) => item.elevatorId === selectedElevatorId)) {
      setSelectedElevatorId(preview.elevators[0].elevatorId);
    }
  }, [preview.elevators, selectedElevatorId]);

  const openSimulation = (simulationId: string) => {
    setActiveSimulationId(simulationId);
    setScreenMode('simulation');
  };

  const goToLobby = () => {
    setScreenMode('lobby');
  };

  const handleCreateSimulation = async (input: CreateSimulationInput) => {
    await withBusy(async () => {
      const created = await createSimulation(input);
      await refreshSimulationList();
      setActiveSimulationId(created.id);
      setScreenMode('simulation');
    });
  };

  const handleUpdateSimulationConfig = async (simulationId: string, input: CreateSimulationInput) => {
    await withBusy(async () => {
      await updateSimulationConfig(simulationId, input);
      await refreshSimulationList();
    });
  };

  const handleDeleteSimulation = async (simulationId: string) => {
    await withBusy(async () => {
      await deleteSimulation(simulationId);

      if (activeSimulationId === simulationId) {
        setActiveSimulationId(null);
        setScreenMode('lobby');
      }

      await refreshSimulationList();
    });
  };

  const handleLifecycleAction = async (action: 'start' | 'pause' | 'resume' | 'reset') => {
    const endpoint = action === 'resume' ? 'start' : action;
    await runForActiveSimulation(
      (simulationId) => lifecycleAction(simulationId, endpoint),
      true,
    );
  };

  const handleManualCall = async (
    originFloor: number,
    destinationFloor: number,
    passengerWeight: number,
  ): Promise<void> => {
    if (mode !== 'manual') {
      return;
    }

    await runForActiveSimulation((simulationId) =>
      enqueueManualCall(simulationId, originFloor, destinationFloor, passengerWeight),
    );
  };

  const handleActivateEmergency = async () => {
    await runForRunningSimulation((simulationId) => setSimulationCondition(simulationId, 'Emergency'));
  };

  const handleClearEmergency = async () => {
    await runForRunningSimulation((simulationId) => setSimulationCondition(simulationId, 'Normal'));
  };

  const handleActivateOverload = async (elevatorId: string) => {
    await runForRunningSimulation((simulationId) => setElevatorCondition(simulationId, elevatorId, 'Overloaded'));
  };

  const handleClearOverload = async (elevatorId: string) => {
    await runForRunningSimulation((simulationId) => setElevatorCondition(simulationId, elevatorId, 'Normal'));
  };

  const handleDisableService = async (elevatorId: string) => {
    await runForRunningSimulation((simulationId) =>
      setElevatorCondition(simulationId, elevatorId, 'PendingOutOfService'),
    );
  };

  const handleEnableService = async (elevatorId: string) => {
    await runForRunningSimulation((simulationId) => setElevatorCondition(simulationId, elevatorId, 'Normal'));
  };

  return {
    screenMode,
    simulations,
    configOptions,
    activeSimulation,
    mode,
    algorithm,
    isBusy,
    isLive,
    preview,
    previewViewModel,
    isSimulationRunning,
    visualizerSnapKey,
    selectedElevatorId,
    setSelectedElevatorId,
    goToLobby,
    openSimulation,
    handleCreateSimulation,
    handleUpdateSimulationConfig,
    handleDeleteSimulation,
    handleLifecycleAction,
    handleManualCall,
    handleActivateEmergency,
    handleClearEmergency,
    handleActivateOverload,
    handleClearOverload,
    handleDisableService,
    handleEnableService,
  };
}
