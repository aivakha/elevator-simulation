import { Button } from './Button';

type SafetyPanelProps = {
  isEmergencyMode: boolean;
  onActivateEmergency: () => Promise<void>;
  onClearEmergency: () => Promise<void>;
  isBusy: boolean;
  isSimulationRunning: boolean;
};

export function SafetyPanel({
  isEmergencyMode,
  onActivateEmergency,
  onClearEmergency,
  isBusy,
  isSimulationRunning,
}: SafetyPanelProps) {
  const controlsDisabled = isBusy || !isSimulationRunning;

  return (
    <section className="sim-card">
      <h2 className="sim-section-title">Safety Controls</h2>

      <div className="mt-3 inset-panel">
        <p className="text-xs font-semibold text-slate-500">Emergency mode: {isEmergencyMode ? 'ACTIVE' : 'OFF'}</p>
        <div className="mt-2 grid grid-cols-2 gap-2">
          <Button type="button" variant="danger" disabled={controlsDisabled || isEmergencyMode} onClick={() => void onActivateEmergency()}>
            Activate
          </Button>
          <Button type="button" disabled={controlsDisabled || !isEmergencyMode} onClick={() => void onClearEmergency()}>
            Clear
          </Button>
        </div>
        {!isSimulationRunning && <p className="mt-2 hint-text">Safety controls are available only while simulation is running.</p>}
      </div>
    </section>
  );
}
