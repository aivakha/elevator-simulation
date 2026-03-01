import { toTitleCase } from '../lib/format';
import type { QueueElevator } from '../../model/types';

type ElevatorFleetTableProps = {
  elevators: QueueElevator[];
  selectedElevatorId: string;
  onSelectElevator: (elevatorId: string) => void;
};

export function ElevatorFleetTable({
  elevators,
  selectedElevatorId,
  onSelectElevator,
}: ElevatorFleetTableProps) {
  const sorted = [...elevators].sort((a, b) => a.elevatorId.localeCompare(b.elevatorId));

  function doorStateLabel(doorState: string): string {
    return `Door ${toTitleCase(doorState)}`;
  }

  return (
    <section className="sim-card">
      <h3 className="sim-section-title">Elevator Fleet</h3>

      {sorted.length === 0 ? (
        <div className="mt-2 inset-panel text-sm text-slate-600">
          Waiting for runtime state…
        </div>
      ) : (
        <div className="mt-2 fleet-scroll">
          <div className="fleet-grid fleet-header">
            <div>Car</div>
            <div>Floor</div>
            <div>Dir</div>
            <div>Queue</div>
            <div>Door State</div>
            <div>Load</div>
          </div>
          {sorted.map((elevator) => {
            const queuePreview = elevator.plannedStops.length === 0 ? '—' : elevator.plannedStops.slice(0, 4).join('→');
            const isSelected = selectedElevatorId === elevator.elevatorId;
            const dir = elevator.direction === 'up' ? '▲' : elevator.direction === 'down' ? '▼' : '•';
            const loadRatio = elevator.capacity > 0 ? (elevator.currentLoad / elevator.capacity) : 0;
            const loadPct = Math.max(0, Math.min(100, loadRatio * 100));
            const isNearOrAtCapacity = loadRatio >= 0.9;
            const loadFillClass = elevator.currentLoad > elevator.capacity
              ? 'load-fill--danger'
              : elevator.condition === 'Overloaded' || isNearOrAtCapacity
                ? 'load-fill--warning'
                : 'load-fill--normal';
            const readableDoorState = doorStateLabel(elevator.doorState);

            return (
              <button
                key={elevator.elevatorId}
                type="button"
                onClick={() => onSelectElevator(elevator.elevatorId)}
                className={`fleet-row${isSelected ? ' fleet-row--selected' : ''}`}
              >
                <div className="fleet-grid">
                  <div className="font-semibold text-slate-900">{elevator.elevatorId}</div>
                  <div className="tabular-nums">{elevator.currentFloor}</div>
                  <div className="text-center text-slate-500">{dir}</div>
                  <div className="truncate font-mono text-[11px] text-slate-600">{queuePreview}</div>
                  <div className="truncate text-[11px] text-slate-700">{readableDoorState}</div>
                  <div title={`${elevator.currentLoad}/${elevator.capacity} lb`}>
                    <div className="load-bar--sm">
                      <div className={`load-fill ${loadFillClass}`} style={{ width: `${loadPct}%` }} />
                    </div>
                  </div>
                </div>
              </button>
            );
          })}
        </div>
      )}
    </section>
  );
}
