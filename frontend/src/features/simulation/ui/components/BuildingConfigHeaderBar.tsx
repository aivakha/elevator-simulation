import type { DispatchAlgorithm, SimulationMode } from '../../model/types';

function modeLabel(mode: SimulationMode): string {
  if (mode === 'morningPeak') {
    return 'Morning';
  }

  if (mode === 'eveningPeak') {
    return 'Evening';
  }

  if (mode === 'manual') {
    return 'Manual';
  }

  return 'Regular';
}

function algorithmLabel(algorithm: DispatchAlgorithm): string {
  if (algorithm === 'nearestCar') {
    return 'Nearest';
  }

  return algorithm.toUpperCase();
}

function Item({ label, value }: { label: string; value: string }) {
  return (
    <div className="data-pill">
      <span className="data-pill-label">{label}</span>
      <span className="data-pill-value">{value}</span>
    </div>
  );
}

type BuildingConfigHeaderBarProps = {
  floors: number;
  elevators: number;
  capacityPerElevator: number;
  doorOpenSeconds: number;
  emergencyDescentMultiplier: number;
  mode: SimulationMode;
  algorithm: DispatchAlgorithm;
};

export function BuildingConfigHeaderBar({
  floors,
  elevators,
  capacityPerElevator,
  doorOpenSeconds,
  emergencyDescentMultiplier,
  mode,
  algorithm,
}: BuildingConfigHeaderBarProps) {
  return (
    <div className="flex flex-wrap items-center gap-2">
      <Item label="Floors" value={String(floors)} />
      <Item label="Cars" value={String(elevators)} />
      <Item label="Capacity" value={`${capacityPerElevator} lb`} />
      <Item label="Door" value={`${doorOpenSeconds}s`} />
      <Item label="Emergency" value={`${emergencyDescentMultiplier}x`} />
      <Item label="Algo" value={algorithmLabel(algorithm)} />
      <Item label="Mode" value={modeLabel(mode)} />
    </div>
  );
}
