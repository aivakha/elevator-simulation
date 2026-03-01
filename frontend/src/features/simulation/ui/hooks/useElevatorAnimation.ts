import { useEffect, useRef, useState } from 'react';

const movingSpeedFloorsPerSecond = 1;
const defaultNearStopDistance = 0.4;

type ElevatorSnapshot = {
  position: number;
  speed: number;
  slowdownDistance?: number;
};

export function useElevatorAnimation(
  count: number,
  floors: number,
  getSnapshot: (index: number) => ElevatorSnapshot,
  snapToTargetsKey?: string,
): number[] {
  const targetsRef   = useRef<number[]>([]);
  const speedsRef    = useRef<number[]>([]);
  const slowdownDistancesRef = useRef<number[]>([]);

  const [displayPos, setDisplayPos] = useState<number[]>(() => Array.from({ length: count }, () => 0));

  useEffect(() => {
    setDisplayPos((prev) => {
      if (prev.length === count) {
        return prev;
      }

      return Array.from({ length: count }, (_, index) => prev[index] ?? 0);
    });
  }, [count]);

  useEffect(() => {
    const maxFloor = Math.max(0, floors - 1);
    setDisplayPos(
      Array.from({ length: count }, (_, index) =>
        Math.max(0, Math.min(maxFloor, targetsRef.current[index] ?? 0)),
      ),
    );
  }, [count, floors, snapToTargetsKey]);

  useEffect(() => {
    let raf = 0;
    let last = performance.now();

    const loop = (now: number) => {
      const dt = Math.min(0.05, Math.max(0, (now - last) / 1000));
      last = now;

      setDisplayPos((prev) => {
        if (targetsRef.current.length === 0) {
          return prev;
        }

        const maxFloor = Math.max(0, floors - 1);
        let changed = false;

        const next = prev.map((position, index) => {
          const target = Math.max(0, Math.min(maxFloor, targetsRef.current[index] ?? 0));
          const delta  = target - position;

          if (Math.abs(delta) < 0.0001) {
            return target;
          }

          const configuredSpeed = Math.max(0.5, speedsRef.current[index] ?? movingSpeedFloorsPerSecond);
          const slowdownDistance = Math.max(0.05, slowdownDistancesRef.current[index] ?? defaultNearStopDistance);
          const nearStopMultiplier = Math.abs(delta) >= slowdownDistance
            ? 1
            : 0.45 + (Math.abs(delta) / slowdownDistance) * 0.55;

          const maxStep = configuredSpeed * nearStopMultiplier * dt;
          const step    = Math.abs(delta) <= maxStep ? delta : Math.sign(delta) * maxStep;
          const output  = position + step;
          changed = changed || Math.abs(output - position) > 0.000001;

          return output;
        });

        return changed ? next : prev;
      });

      raf = requestAnimationFrame(loop);
    };

    raf = requestAnimationFrame(loop);

    return () => cancelAnimationFrame(raf);
  }, [floors]);

  targetsRef.current  = Array.from({ length: count }, (_, index) => getSnapshot(index).position);
  speedsRef.current   = Array.from({ length: count }, (_, index) => getSnapshot(index).speed);
  slowdownDistancesRef.current = Array.from(
    { length: count },
    (_, index) => getSnapshot(index).slowdownDistance ?? defaultNearStopDistance,
  );

  return displayPos;
}
