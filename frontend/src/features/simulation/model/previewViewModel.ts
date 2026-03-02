import type { ElevatorCondition, QueueElevator, QueuePreview } from './types';

type PreviewViewModel = {
  selectedElevator: QueueElevator | null;
  selectedElevatorAssignedPickupCount: number;
  selectedElevatorPickedUpCount: number;
  selectedElevatorDroppedOffCount: number;
  waitingCount: number;
  pickedUpCount: number;
  droppedOffCount: number;
  totalCount: number;
  outOfServiceElevatorIds: string[];
  pendingOutOfServiceElevatorIds: string[];
  overloadedElevatorIds: string[];
};

export function buildPreviewViewModel(preview: QueuePreview, selectedElevatorId: string): PreviewViewModel {
  const selectedElevator = preview.elevators.find((item) => item.elevatorId === selectedElevatorId) ?? null;

  const elevatorIdsByCondition = preview.elevators.reduce(
    (acc, e) => {
      (acc[e.condition] ??= []).push(e.elevatorId);
      return acc;
    },
    {} as Partial<Record<ElevatorCondition, string[]>>,
  );

  return {
    selectedElevator,
    selectedElevatorAssignedPickupCount: selectedElevator?.assignedPickupCount ?? 0,
    selectedElevatorPickedUpCount: selectedElevator?.pickedUpPassengers ?? 0,
    selectedElevatorDroppedOffCount: selectedElevator?.droppedOffPassengers ?? 0,
    waitingCount: preview.waitingPassengers,
    pickedUpCount: preview.pickedUpPassengers,
    droppedOffCount: preview.droppedOffPassengers,
    totalCount: preview.totalPassengers,
    outOfServiceElevatorIds: elevatorIdsByCondition['OutOfService'] ?? [],
    pendingOutOfServiceElevatorIds: elevatorIdsByCondition['PendingOutOfService'] ?? [],
    overloadedElevatorIds: elevatorIdsByCondition['Overloaded'] ?? [],
  };
}
