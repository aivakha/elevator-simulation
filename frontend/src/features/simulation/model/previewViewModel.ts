import type { QueueElevator, QueuePreview } from './types';

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

  return {
    selectedElevator,
    selectedElevatorAssignedPickupCount: selectedElevator?.assignedPickupCount ?? 0,
    selectedElevatorPickedUpCount: selectedElevator?.pickedUpPassengers ?? 0,
    selectedElevatorDroppedOffCount: selectedElevator?.droppedOffPassengers ?? 0,
    waitingCount: preview.waitingPassengers ?? 0,
    pickedUpCount: preview.pickedUpPassengers ?? 0,
    droppedOffCount: preview.droppedOffPassengers ?? 0,
    totalCount: preview.totalPassengers ?? ((preview.waitingPassengers ?? 0) + (preview.pickedUpPassengers ?? 0) + (preview.droppedOffPassengers ?? 0)),
    outOfServiceElevatorIds: preview.elevators
      .filter((item) => item.condition === 'OutOfService')
      .map((item) => item.elevatorId),
    pendingOutOfServiceElevatorIds: preview.elevators
      .filter((item) => item.condition === 'PendingOutOfService')
      .map((item) => item.elevatorId),
    overloadedElevatorIds: preview.elevators
      .filter((item) => item.condition === 'Overloaded')
      .map((item) => item.elevatorId),
  };
}
