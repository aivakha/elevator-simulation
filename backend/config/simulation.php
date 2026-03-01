<?php


return [
    'defaults' => [
        'name'                        => 'New Simulation',
        'floors'                      => 10,
        'elevators'                   => 4,
        'capacityPerElevator'         => 6000,
        'tickIntervalMs'              => 1000,
        'doorOpenSeconds'             => 1,
        'floorTravelSeconds'          => 1,
        'emergencyDescentMultiplier'  => 2,
        'maxPendingCalls'             => 200,
        'mode'                        => 'regular',
        'algorithm'                   => 'scan',
    ],
    'limits' => [
        'minElevators'                      => 1,
        'maxElevators'                      => 5,
        'minFloors'                         => 2,
        'maxFloors'                         => 20,
        'minCapacityPerElevator'            => 2000,
        'maxCapacityPerElevator'            => 10000,
        'minDoorOpenSeconds'                => 1,
        'maxDoorOpenSeconds'                => 10,
        'minEmergencyDescentMultiplier'     => 1,
        'maxEmergencyDescentMultiplier'     => 4,
    ],
];
