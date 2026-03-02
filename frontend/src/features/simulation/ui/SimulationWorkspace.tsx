import { useState } from 'react';
import { BuildingConfigHeaderBar } from './components/BuildingConfigHeaderBar';
import { Button } from './components/Button';
import { ControlPanel } from './components/ControlPanel';
import { DefaultZonesPanel } from './components/DefaultZonesPanel';
import { ElevatorFleetTable } from './components/ElevatorFleetTable';
import { ElevatorInspectorDock } from './components/ElevatorInspectorDock';
import { ElevatorShaftVisualizer } from './components/ElevatorShaftVisualizer';
import { ManualModePanel } from './components/ManualModePanel';
import { RunStatsPanel } from './components/RunStatsPanel';
import { SafetyPanel } from './components/SafetyPanel';
import { SimulationDocsPage } from './components/SimulationDocsPage';
import { SimulationLobby } from './components/SimulationLobby';
import { useSimulationWorkspaceController } from '../model/useSimulationWorkspaceController';
import { simulationStatusPill } from './lib/format';

export function SimulationWorkspace() {
  const controller = useSimulationWorkspaceController();
  const [isDocsOpen, setIsDocsOpen] = useState(false);

  if (isDocsOpen) {
    return <SimulationDocsPage onBack={() => setIsDocsOpen(false)} />;
  }

  if (controller.screenMode === 'lobby') {
    return (
      <SimulationLobby
        simulations={controller.simulations}
        isBusy={controller.isBusy}
        onOpenDocumentation={() => setIsDocsOpen(true)}
        onOpenSimulation={controller.openSimulation}
        onCreateSimulation={controller.handleCreateSimulation}
        onUpdateSimulationConfig={controller.handleUpdateSimulationConfig}
        onDeleteSimulation={controller.handleDeleteSimulation}
      />
    );
  }

  const activeStatusPill = simulationStatusPill(controller.activeSimulation?.status ?? 'unknown');

  return (
    <div className="app-shell">
      <header className="app-header">
        <div className="app-header-inner">
          <div className="app-header-row">
            <div className="app-header-left">
              <Button type="button" size="sm" onClick={controller.goToLobby} disabled={controller.isBusy}>
                Back To Simulations
              </Button>
              <Button type="button" size="sm" onClick={() => setIsDocsOpen(true)} disabled={controller.isBusy}>
                Documentation
              </Button>
              <h1 className="app-title">{controller.activeSimulation?.name ?? 'Simulation'}</h1>
              <span className={`header-pill ${activeStatusPill.className}`}>
                <span className={`header-pill-dot ${activeStatusPill.dotClassName}`} />
                {activeStatusPill.label}
              </span>
              {controller.preview.isEmergencyMode && (
                <span className="header-pill header-pill--emergency">
                  <span className="header-pill-dot animate-pulse bg-red-600" />
                  Emergency
                </span>
              )}
              {controller.previewViewModel.overloadedElevatorIds.length > 0 && (
                <span className="header-pill header-pill--overload">
                  <span className="header-pill-dot animate-pulse bg-amber-500" />
                  Overloaded: {controller.previewViewModel.overloadedElevatorIds.join(', ')}
                </span>
              )}
              {controller.previewViewModel.outOfServiceElevatorIds.length > 0 && (
                <span className="header-pill header-pill--oos">
                  <span className="header-pill-dot animate-pulse bg-sky-600" />
                  Out of Service: {controller.previewViewModel.outOfServiceElevatorIds.join(', ')}
                </span>
              )}
              {controller.previewViewModel.pendingOutOfServiceElevatorIds.length > 0 && (
                <span className="header-pill header-pill--pending">
                  <span className="header-pill-dot animate-pulse bg-cyan-600" />
                  Service Pending: {controller.previewViewModel.pendingOutOfServiceElevatorIds.join(', ')}
                </span>
              )}
            </div>
            <span className={`header-pill ${controller.isLive ? 'header-pill--live' : 'header-pill--offline'}`}>
              <span className={`header-pill-dot ${controller.isLive ? 'bg-emerald-500' : 'bg-red-500'}`} />
              Websocket
            </span>
          </div>
        </div>

        <div className="app-subheader">
          <div className="app-subheader-row">
            {controller.activeSimulation && (
              <BuildingConfigHeaderBar
                floors={controller.activeSimulation.config_json.floors}
                elevators={controller.activeSimulation.config_json.elevators}
                capacityPerElevator={Math.max(2000, controller.activeSimulation.config_json.capacityPerElevator)}
                doorOpenSeconds={controller.activeSimulation.config_json.doorOpenSeconds}
                emergencyDescentMultiplier={controller.activeSimulation.config_json.emergencyDescentMultiplier ?? 2}
                mode={controller.mode}
                algorithm={controller.algorithm}
              />
            )}
            <RunStatsPanel
              waitingCount={controller.previewViewModel.waitingCount}
              pickedUpCount={controller.previewViewModel.pickedUpCount}
              droppedOffCount={controller.previewViewModel.droppedOffCount}
              totalCount={controller.previewViewModel.totalCount}
            />
          </div>
        </div>
      </header>

      <div className="app-layout-grid">
        <aside className="app-left-panel">
          <div className="app-panel-stack">
            <ControlPanel
              onLifecycleAction={controller.handleLifecycleAction}
              isBusy={controller.isBusy}
              status={controller.activeSimulation?.status}
            />

            {controller.activeSimulation && <DefaultZonesPanel zones={controller.activeSimulation.zones} />}

            <SafetyPanel
              isEmergencyMode={controller.preview.isEmergencyMode}
              onActivateEmergency={controller.handleActivateEmergency}
              onClearEmergency={controller.handleClearEmergency}
              isBusy={controller.isBusy}
              isSimulationRunning={controller.isSimulationRunning}
            />

            {controller.mode === 'manual' ? (
              <ManualModePanel
                enabled={controller.isSimulationRunning}
                floors={controller.activeSimulation?.config_json.floors ?? 20}
                onSubmit={controller.handleManualCall}
                isBusy={controller.isBusy}
              />
            ) : (
              <div className="app-manual-disabled-card">
                Automatic traffic is active in {controller.mode}. Manual calls are disabled.
              </div>
            )}
          </div>
        </aside>

        <main className="app-main-panel">
          <div className="app-main-content">
            <ElevatorShaftVisualizer
              elevators={controller.preview.elevators}
              floors={controller.activeSimulation?.config_json.floors ?? 20}
              isEmergencyMode={controller.preview.isEmergencyMode}
              snapKey={controller.visualizerSnapKey}
              emergencyDescentMultiplier={controller.activeSimulation?.config_json.emergencyDescentMultiplier ?? 2}
              floorTravelSeconds={controller.preview.floorTravelSeconds}
              tickIntervalMs={controller.preview.tickIntervalMs}
            />
          </div>
        </main>

        <aside className="app-right-panel">
          <div className="app-panel-stack">
            <ElevatorFleetTable
              elevators={controller.preview.elevators}
              selectedElevatorId={controller.selectedElevatorId}
              onSelectElevator={controller.setSelectedElevatorId}
            />

            <ElevatorInspectorDock
              elevator={controller.previewViewModel.selectedElevator}
              waitingCount={controller.previewViewModel.selectedElevatorAssignedPickupCount}
              pickedUpCount={controller.previewViewModel.selectedElevatorPickedUpCount}
              droppedOffCount={controller.previewViewModel.selectedElevatorDroppedOffCount}
              isEmergencyMode={controller.preview.isEmergencyMode}
              isBusy={controller.isBusy}
              isSimulationRunning={controller.isSimulationRunning}
              onTriggerOverload={controller.handleActivateOverload}
              onClearOverload={controller.handleClearOverload}
              onDisableService={controller.handleDisableService}
              onEnableService={controller.handleEnableService}
            />
          </div>
        </aside>
      </div>
    </div>
  );
}
