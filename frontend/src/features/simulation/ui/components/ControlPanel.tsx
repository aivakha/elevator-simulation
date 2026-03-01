import { Button } from './Button';
import type { SimulationSummary } from '../../model/types';

type ControlPanelProps = {
  onLifecycleAction: (action: 'start' | 'pause' | 'resume' | 'reset') => void;
  isBusy: boolean;
  status?: SimulationSummary['status'];
};

export function ControlPanel({
  onLifecycleAction,
  isBusy,
  status = 'draft',
}: ControlPanelProps) {
  const isRunning = status === 'running';
  const isPaused = status === 'paused';
  const isDraft = status === 'draft';

  const pauseResumeDisabled = isBusy || (!isRunning && !isPaused);
  const resetDisabled = isBusy || isDraft;
  const startDisabled = isBusy || isRunning || isPaused;
  const pauseResumeLabel = isRunning ? 'Pause' : 'Resume';

  return (
    <section className="sim-card">
      <h2 className="sim-section-title">Simulation Controls</h2>

      <div className="mt-3 grid grid-cols-3 gap-2">
        <Button type="button" onClick={() => onLifecycleAction('start')} disabled={startDisabled} variant="primary" size="sm">Start</Button>
        <Button
          type="button"
          onClick={() => onLifecycleAction(isRunning ? 'pause' : 'resume')}
          disabled={pauseResumeDisabled}
          size="sm"
        >
          {pauseResumeLabel}
        </Button>
        <Button type="button" onClick={() => onLifecycleAction('reset')} disabled={resetDisabled} size="sm">Reset</Button>
      </div>
    </section>
  );
}
