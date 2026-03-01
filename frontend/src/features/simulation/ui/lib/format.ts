import type { SimulationSummary } from '../../model/types';

/**
 * Converts a camelCase or snake_case string to Title Case with spaces.
 * e.g. "morningPeak" → "Morning Peak", "door_open" → "Door Open"
 */
export function toTitleCase(value: string): string {
  return value
    .replace(/([a-z])([A-Z])/g, '$1 $2')
    .replace(/[_-]+/g, ' ')
    .trim()
    .split(/\s+/)
    .map((token) => token.charAt(0).toUpperCase() + token.slice(1).toLowerCase())
    .join(' ');
}

export type StatusPill = {
  className: string;
  label: string;
  dotClassName: string;
};

/**
 * Returns Tailwind class names and a display label for a simulation status badge.
 */
export function simulationStatusPill(status: SimulationSummary['status'] | 'unknown'): StatusPill {
  switch (status) {
    case 'running':
      return {
        className: 'sim-status-pill--running',
        label: 'Running',
        dotClassName: 'sim-status-dot--running',
      };
    case 'paused':
      return {
        className: 'sim-status-pill--paused',
        label: 'Paused',
        dotClassName: 'sim-status-dot--paused',
      };
    case 'completed':
      return {
        className: 'sim-status-pill--completed',
        label: 'Completed',
        dotClassName: 'sim-status-dot--completed',
      };
    case 'draft':
      return {
        className: 'sim-status-pill--draft',
        label: 'Draft',
        dotClassName: 'sim-status-dot--draft',
      };
    default:
      return {
        className: 'sim-status-pill--unknown',
        label: 'Unknown',
        dotClassName: 'sim-status-dot--unknown',
      };
  }
}
