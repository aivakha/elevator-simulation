export function normalizeDisplayFloor(position: number, velocity: number, maxFloor: number): number {
  const threshold = 0.75;
  const offset = 1 - threshold;

  const raw =
    velocity > 0.01
      ? Math.floor(position + offset)
      : velocity < -0.01
        ? Math.ceil(position - offset)
        : Math.round(position);

  return Math.max(0, Math.min(maxFloor, raw));
}
