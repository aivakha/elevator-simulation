import { toTitleCase } from '../lib/format';
import type { QueueElevator } from '../../model/types';
import { Button } from './Button';

type ElevatorInspectorDockProps = {
  elevator: QueueElevator | null;
  waitingCount: number;
  pickedUpCount: number;
  droppedOffCount: number;
  isEmergencyMode: boolean;
  isBusy: boolean;
  isSimulationRunning: boolean;
  onTriggerOverload: (elevatorId: string) => Promise<void>;
  onClearOverload: (elevatorId: string) => Promise<void>;
  onDisableService: (elevatorId: string) => Promise<void>;
  onEnableService: (elevatorId: string) => Promise<void>;
};

export function ElevatorInspectorDock({
  elevator,
  waitingCount,
  pickedUpCount,
  droppedOffCount,
  isEmergencyMode,
  isBusy,
  isSimulationRunning,
  onTriggerOverload,
  onClearOverload,
  onDisableService,
  onEnableService,
}: ElevatorInspectorDockProps) {
  if (elevator === null) {
    return null;
  }

  const loadRatio = elevator.capacity > 0 ? (elevator.currentLoad / elevator.capacity) : 0;
  const loadPct = Math.max(0, Math.min(100, loadRatio * 100));
  const isNearOrAtCapacity = loadRatio >= 0.9;
  const loadFillClass = elevator.currentLoad > elevator.capacity
    ? 'load-fill--danger'
    : elevator.condition === 'Overloaded' || isNearOrAtCapacity
      ? 'load-fill--warning'
      : 'load-fill--normal';
  const isElevatorEmergency = elevator.condition === 'Emergency';
  const showEmergencyPill = isEmergencyMode && isElevatorEmergency && elevator.condition !== 'OutOfService';

  return (
    <section className="sim-card">
      <div className="flex items-center justify-between">
        <div className="text-sm font-semibold text-slate-900">{elevator.elevatorId}</div>
        <div className="flex flex-wrap items-center justify-end gap-2">
          {showEmergencyPill && (
            <span className="status-badge status-badge--emergency">
              <span className="status-badge-dot animate-pulse bg-red-500" />
              Emergency
            </span>
          )}

          {elevator.condition === 'OutOfService' && (
            <span className="status-badge status-badge--oos">
              <span className="status-badge-dot bg-sky-500" />
              Out Of Service
            </span>
          )}

          {elevator.condition === 'PendingOutOfService' && (
            <span className="status-badge status-badge--oos">
              <span className="status-badge-dot bg-sky-500" />
              Service Exit Pending
            </span>
          )}

          {elevator.condition === 'Overloaded' && (
            <span className="status-badge status-badge--overload">
              <span className="status-badge-dot bg-amber-500" />
              Overload
            </span>
          )}
        </div>
      </div>

      <div className="mt-3 grid grid-cols-3 gap-2 text-xs">
        <div className="stat-cell">
          <div className="stat-label">Floor</div>
          <div className="stat-value">{elevator.currentFloor}</div>
        </div>
        <div className="stat-cell">
          <div className="stat-label">Direction</div>
          <div className="stat-value">{elevator.direction}</div>
        </div>
        <div className="stat-cell">
          <div className="stat-label">Door State</div>
          <div className="stat-value">{toTitleCase(elevator.doorState)}</div>
        </div>
      </div>

      <div className="mt-3">
        <div className="flex items-center justify-between text-xs text-slate-500">
          <span>Load</span>
          <span className="tabular-nums">{elevator.currentLoad}/{elevator.capacity}</span>
        </div>
        <div className="mt-1 load-bar">
          <div className={`load-fill ${loadFillClass}`} style={{ width: `${loadPct}%` }} />
        </div>
      </div>

      <div className="mt-3 stat-cell">
        <div className="hint-text">Stops</div>
        <div className="mt-1 truncate font-mono text-[12px] text-slate-700">
          {elevator.plannedStops.length === 0 ? '—' : elevator.plannedStops.join(' \u2192 ')}
        </div>
      </div>

      <div className="mt-3 grid grid-cols-3 gap-2 text-xs">
        <div className="stat-cell">
          <div className="stat-label">Assigned Pickups</div>
          <div className="stat-value">{waitingCount}</div>
        </div>
        <div className="stat-cell">
          <div className="stat-label">Passengers Picked Up</div>
          <div className="stat-value">{pickedUpCount}</div>
        </div>
        <div className="stat-cell">
          <div className="stat-label">Passengers Dropped Off</div>
          <div className="stat-value">{droppedOffCount}</div>
        </div>
      </div>

      <div className="mt-4 grid grid-cols-2 gap-2">
        <Button
          type="button"
          variant="warning"
          disabled={!isSimulationRunning || isBusy || elevator.overloadSavedLoad !== null || isElevatorEmergency || elevator.condition === 'OutOfService' || elevator.condition === 'PendingOutOfService'}
          onClick={() => void onTriggerOverload(elevator.elevatorId)}
        >
          {elevator.overloadSavedLoad !== null ? 'Overload Active' : 'Trigger Overload'}
        </Button>
        <Button
          type="button"
          disabled={!isSimulationRunning || isBusy || isElevatorEmergency || elevator.overloadSavedLoad === null || elevator.condition === 'OutOfService' || elevator.condition === 'PendingOutOfService'}
          onClick={() => void onClearOverload(elevator.elevatorId)}
        >
          Clear Overload
        </Button>
      </div>

      <div className="mt-2 grid grid-cols-2 gap-2">
        <Button
          type="button"
          variant="danger"
          disabled={!isSimulationRunning || isBusy || isElevatorEmergency || elevator.condition === 'OutOfService' || elevator.condition === 'PendingOutOfService'}
          onClick={() => void onDisableService(elevator.elevatorId)}
        >
          Disable Service
        </Button>
        <Button
          type="button"
          disabled={!isSimulationRunning || isBusy || isElevatorEmergency || (elevator.condition !== 'OutOfService' && elevator.condition !== 'PendingOutOfService')}
          onClick={() => void onEnableService(elevator.elevatorId)}
        >
          Clear Service
        </Button>
      </div>

      {!isSimulationRunning && <p className="mt-2 hint-text">Elevator controls are available only while simulation is running.</p>}
    </section>
  );
}
