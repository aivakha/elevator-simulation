export type SimulationMode = 'manual' | 'regular' | 'morningPeak' | 'eveningPeak';

export type ElevatorState = 'Idle' | 'Moving';

export type ElevatorCondition = 'Normal' | 'Emergency' | 'Overloaded' | 'PendingOutOfService' | 'OutOfService';
export type SimulationConditionControl = 'Normal' | 'Emergency';
export type ElevatorConditionControl = 'Normal' | 'Overloaded' | 'PendingOutOfService';

export type ElevatorZone = {
  elevatorId: string;
  zoneStart: number;
  zoneEnd: number;
};

export type DispatchAlgorithm = 'nearestCar' | 'scan' | 'look';

export type QueueElevator = {
  elevatorId: string;
  currentFloor: number;
  currentLoad: number;
  capacity: number;
  pickedUpPassengers: number;
  droppedOffPassengers: number;
  direction: 'up' | 'down' | 'idle';
  state: ElevatorState;
  condition: ElevatorCondition;
  doorState: 'closed' | 'opening' | 'open' | 'closing';
  overloadSavedLoad: number | null;
  plannedStops: number[];
};

export type QueuePreview = {
  simulationId: string;
  tickNumber: number;
  isEmergencyMode: boolean;
  tickIntervalMs: number;
  floorTravelSeconds: number;
  maxPendingCalls: number;
  waitingPassengers: number;
  pickedUpPassengers: number;
  droppedOffPassengers: number;
  elevators: QueueElevator[];
  pendingHallCalls: Array<Record<string, string | number | null>>;
};

export type SimulationSummary = {
  id: string;
  name: string;
  created_at?: string;
  updated_at?: string;
  status: 'draft' | 'running' | 'paused' | 'completed';
  config_json: {
    floors: number;
    elevators: number;
    capacityPerElevator: number;
    doorOpenSeconds: number;
    emergencyDescentMultiplier: number;
    mode: SimulationMode;
    algorithm: DispatchAlgorithm;
  };
  zones: ElevatorZone[];
};

export type CreateSimulationInput = {
  id?: string;
  name: string;
  floors: number;
  elevators: number;
  capacityPerElevator: number;
  doorOpenSeconds: number;
  mode: SimulationMode;
  algorithm: DispatchAlgorithm;
};

export type SimulationConfigDefaults = {
  name: string;
  floors: number;
  elevators: number;
  capacityPerElevator: number;
  doorOpenSeconds: number;
  emergencyDescentMultiplier: number;
  mode: SimulationMode;
  algorithm: DispatchAlgorithm;
};

export type SimulationConfigLimits = {
  minElevators: number;
  maxElevators: number;
  minFloors: number;
  maxFloors: number;
  minCapacityPerElevator: number;
  maxCapacityPerElevator: number;
  minDoorOpenSeconds: number;
  maxDoorOpenSeconds: number;
  minEmergencyDescentMultiplier: number;
  maxEmergencyDescentMultiplier: number;
};

export type SimulationConfigOptions = {
  defaults: SimulationConfigDefaults;
  limits: SimulationConfigLimits;
};
