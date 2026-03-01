import { useEffect, useMemo, useState } from 'react';
import {
  createSimulation,
  deleteSimulation,
  enqueueManualCall,
  lifecycleAction,
  listSimulations,
  setElevatorCondition,
  setSimulationCondition,
  updateSimulationConfig,
} from '../api/simulationApi';
import { buildPreviewViewModel } from './previewViewModel';
import type {
  CreateSimulationInput,
  SimulationSummary,
} from './types';
import { useQueuePreview } from './useQueuePreview';

type ScreenMode = 'lobby' | 'simulation';

type RefreshOptions = {
  refreshList?: boolean;
  forcePreview?: boolean;
};

export function useSimulationWorkspaceController() {
  const [screenMode, setScreenMode] = useState<ScreenMode>('lobby');
  const [simulations, setSimulations] = useState<SimulationSummary[]>([]);
  const [activeSimulationId, setActiveSimulationId] = useState<string | null>(null);
  const [selectedElevatorId, setSelectedElevatorId] = useState('');
  const [isBusy, setIsBusy] = useState(false);

  const { preview, isLive, refresh } = useQueuePreview(activeSimulationId ?? '');

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
    options: RefreshOptions = {},
  ): Promise<T | null> => {
    if (!activeSimulationId) {
      return null;
    }

    return withBusy(async () => {
      const result = await task(activeSimulationId);

      if (options.refreshList) {
        await refreshSimulationList();
      }

      await refresh(options.forcePreview ? { force: true } : undefined);
      return result;
    });
  };

  const runForRunningSimulation = async <T>(
    task: (simulationId: string) => Promise<T>,
    options: RefreshOptions = {},
  ): Promise<T | null> => {
    if (!isSimulationRunning) {
      return null;
    }

    return runForActiveSimulation(task, options);
  };

  useEffect(() => {
    void refreshSimulationList();
  }, []);

  useEffect(() => {
    if (!activeSimulation) {
      return;
    }

    void refresh({ force: true });
  }, [activeSimulation, refresh]);

  useEffect(() => {
    if (preview.elevators.length === 0) {
      setSelectedElevatorId('');
      return;
    }

    if (!preview.elevators.some((item) => item.elevatorId === selectedElevatorId)) {
      setSelectedElevatorId(preview.elevators[0].elevatorId);
    }
  }, [preview.elevators, selectedElevatorId]);

  const openSimulation = async (simulationId: string) => {
    await withBusy(async () => {
      setActiveSimulationId(simulationId);
      setScreenMode('simulation');
      await refresh({ force: true });
    });
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
      await refresh();
    });
  };

  const handleUpdateSimulationConfig = async (simulationId: string, input: CreateSimulationInput) => {
    await withBusy(async () => {
      await updateSimulationConfig(simulationId, input);
      await refreshSimulationList();

      if (activeSimulationId === simulationId) {
        await refresh({ force: true });
      }
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
      { refreshList: true, forcePreview: action === 'reset' },
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
