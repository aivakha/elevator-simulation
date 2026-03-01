import { useMemo } from 'react';
import { useElevatorAnimation } from '../hooks/useElevatorAnimation';
import { normalizeDisplayFloor } from '../lib/runtime';
import type { QueueElevator } from '../../model/types';

type ElevatorShaftVisualizerProps = {
  elevators: QueueElevator[];
  floors: number;
  isEmergencyMode: boolean;
  snapKey: string;
  emergencyDescentMultiplier: number;
  floorTravelSeconds: number;
  tickIntervalMs: number;
};

const stateColors: Record<string, string> = {
  Idle: '#64748b',
  Moving: '#3b82f6',
};

const floorHeight = 42;
const shaftWidth = 60;
const shaftGap = 10;
const labelWidth = 40;
const carHeight = 32;
const carWidth = 48;
const doorEasing = 'cubic-bezier(0.2, 0, 0, 1)';
const baseNearStopDistance = 0.4;
const nearCapacityThreshold = 0.9;

export function ElevatorShaftVisualizer({
  elevators,
  floors,
  isEmergencyMode,
  snapKey,
  emergencyDescentMultiplier,
  floorTravelSeconds,
  tickIntervalMs,
}: ElevatorShaftVisualizerProps) {
  // Door animation duration scales with the tick interval so it always
  // completes well within a single tick — looks correct at any sim speed.
  const doorAnimMs = Math.min(600, Math.max(80, Math.round(tickIntervalMs * 0.38)));
  const orderedElevators = useMemo(() => {
    return [...elevators].sort((a, b) => a.elevatorId.localeCompare(b.elevatorId));
  }, [elevators]);

  const motionSnapshots = useMemo(() => {
    const safeFloorTravelSeconds = Math.max(0.2, floorTravelSeconds);
    const regularFloorsPerSecond = 1 / safeFloorTravelSeconds;
    const emergencyFloorsPerSecond = Math.max(1, emergencyDescentMultiplier) / safeFloorTravelSeconds;
    const emergencySpeedRatio = emergencyFloorsPerSecond / Math.max(0.01, regularFloorsPerSecond);

    // Scale braking distance with the same proportion as emergency speed
    const emergencyNearStopDistance = baseNearStopDistance * Math.max(1, emergencySpeedRatio);

    return orderedElevators.map((elevator) => {
      const isEmergency = elevator.condition === 'Emergency';
      return {
        position: elevator.currentFloor,
        speed: isEmergency ? emergencyFloorsPerSecond : regularFloorsPerSecond,
        slowdownDistance: isEmergency ? emergencyNearStopDistance : baseNearStopDistance,
      };
    });
  }, [orderedElevators, emergencyDescentMultiplier, floorTravelSeconds]);

  const displayPos = useElevatorAnimation(
    orderedElevators.length,
    floors,
    (index) => {
      const snapshot = motionSnapshots[index];
      if (!snapshot) {
        return { position: 0, speed: 1, slowdownDistance: baseNearStopDistance };
      }

      return snapshot;
    },
    snapKey,
  );

  const totalHeight = floors * floorHeight;
  const totalWidth = labelWidth + orderedElevators.length * (shaftWidth + shaftGap);
  const floorLabels = Array.from({ length: floors }, (_, i) => floors - 1 - i);

  return (
    <section className="sim-card">
      <h2 className="sim-section-title">Building View</h2>
      <div className="mt-2 flex w-full justify-center overflow-auto">
        <svg width={totalWidth} height={totalHeight + 24} viewBox={`0 0 ${totalWidth} ${totalHeight + 24}`}>
          {floorLabels.map((floor, index) => (
            <text
              key={`floor-${floor}`}
              x={labelWidth - 8}
              y={index * floorHeight + floorHeight / 2 + 14}
              textAnchor="end"
              className="fill-slate-500"
              fontSize={11}
            >
              {floor}
            </text>
          ))}

          {floorLabels.map((_, index) => (
            <line
              key={`line-${index}`}
              x1={labelWidth}
              y1={index * floorHeight + 10}
              x2={totalWidth}
              y2={index * floorHeight + 10}
              stroke="#dbe5f0"
              strokeWidth={1}
            />
          ))}

          {orderedElevators.map((elevator, index) => {
            const x = labelWidth + index * (shaftWidth + shaftGap);
            const carX = x + (shaftWidth - carWidth) / 2;
            const rawPos = displayPos[index] ?? elevator.currentFloor;
            const clampedPos = Math.max(0, Math.min(floors - 1, rawPos));
            const carY = (floors - 1 - clampedPos) * floorHeight + 10 + (floorHeight - carHeight) / 2;

            const colorBase = stateColors[elevator.state] ?? '#64748b';
            const isDoorPhase =
              elevator.doorState === 'opening' ||
              elevator.doorState === 'open' ||
              elevator.doorState === 'closing';
            const isElevatorEmergency = elevator.condition === 'Emergency';
            const isServiceState =
              elevator.condition === 'OutOfService' || elevator.condition === 'PendingOutOfService';
            const loadRatio = elevator.capacity > 0 ? elevator.currentLoad / elevator.capacity : 0;
            const isNearOrAtCapacity = loadRatio >= nearCapacityThreshold;
            const color = isServiceState
              ? '#0ea5e9'
              : elevator.condition === 'Overloaded' || isNearOrAtCapacity
                ? '#f59e0b'
              : isElevatorEmergency
                ? '#ef4444'
                : isDoorPhase
                  ? '#22c55e'
                  : colorBase;
            const velocity = elevator.direction === 'up' ? 1 : elevator.direction === 'down' ? -1 : 0;
            const displayFloor = normalizeDisplayFloor(clampedPos, velocity, floors - 1);
            const showArrow = elevator.direction !== 'idle';
            const arrow = elevator.direction === 'up' ? '▲' : '▼';
            const isAlignedWithRuntimeFloor = Math.abs(clampedPos - elevator.currentFloor) <= 0.08;
            const wantsDoorsOpen =
              isAlignedWithRuntimeFloor &&
              (elevator.doorState === 'open' || elevator.doorState === 'opening');
            const doorScale = wantsDoorsOpen ? 0.16 : 1;

            return (
              <g key={elevator.elevatorId}>
                <rect
                  x={x + (shaftWidth - carWidth) / 2 - 2}
                  y={10}
                  width={carWidth + 4}
                  height={totalHeight}
                  fill="#f8fafc"
                  stroke="#d8e2ee"
                  strokeWidth={1}
                  rx={3}
                />

                <g style={{ transform: `translate(${carX}px, ${carY}px)` }}>
                  <rect width={carWidth} height={carHeight} fill={color} rx={4}>
                    {isEmergencyMode && !isServiceState && (
                      <animate
                        attributeName="fill"
                        values="#ef4444;#f97316;#ef4444"
                        dur="0.8s"
                        repeatCount="indefinite"
                      />
                    )}
                    {isServiceState && (
                      <animate
                        attributeName="fill"
                        values="#0ea5e9;#f59e0b;#0ea5e9"
                        dur="1.1s"
                        repeatCount="indefinite"
                      />
                    )}
                  </rect>

                  <g>
                    <rect
                      x={0}
                      y={0}
                      width={carWidth / 2}
                      height={carHeight}
                      fill="rgba(255,255,255,0.24)"
                      style={{
                        transform: `scaleX(${doorScale})`,
                        transformBox: 'fill-box',
                        transformOrigin: 'left center',
                        transition: `transform ${doorAnimMs}ms ${doorEasing}`,
                      }}
                    />
                    <rect
                      x={carWidth / 2}
                      y={0}
                      width={carWidth / 2}
                      height={carHeight}
                      fill="rgba(255,255,255,0.24)"
                      style={{
                        transform: `scaleX(${doorScale})`,
                        transformBox: 'fill-box',
                        transformOrigin: 'right center',
                        transition: `transform ${doorAnimMs}ms ${doorEasing}`,
                      }}
                    />
                  </g>

                  <text x={carWidth / 2} y={carHeight / 2 + 4} textAnchor="middle" fill="white" fontSize={12} fontWeight={700}>
                    {displayFloor}
                  </text>

                  {showArrow && (
                    <text x={carWidth / 2} y={-6} textAnchor="middle" fill={color} fontSize={13}>
                      {arrow}
                    </text>
                  )}
                </g>

                <text x={x + shaftWidth / 2} y={totalHeight + 21} textAnchor="middle" className="fill-slate-700" fontSize={10}>
                  {elevator.elevatorId}
                </text>
              </g>
            );
          })}
        </svg>
      </div>
    </section>
  );
}
