import { useState, type FormEvent } from 'react';
import { Button } from './Button';

type ManualModePanelProps = {
  enabled: boolean;
  floors: number;
  onSubmit: (originFloor: number, destinationFloor: number, passengerWeight: number) => Promise<void>;
  isBusy: boolean;
};

export function ManualModePanel({ enabled, floors, onSubmit, isBusy }: ManualModePanelProps) {
  const [originFloor, setOriginFloor] = useState(0);
  const [destinationFloor, setDestinationFloor] = useState(1);
  const [passengerWeight, setPassengerWeight] = useState(180);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!enabled || originFloor === destinationFloor) {
      return;
    }

    await onSubmit(originFloor, destinationFloor, passengerWeight);
  }

  const colCount = floors <= 10 ? 5 : 6;
  const floorItems = Array.from({ length: floors }, (_, index) => index);

  return (
    <section className="sim-card">
      <div className="flex items-center justify-between">
        <h3 className="sim-section-title">Manual Call Panel</h3>
        <div className="text-[11px] font-semibold text-slate-500">manual mode</div>
      </div>

      <form onSubmit={handleSubmit} className="mt-3 grid gap-3">
        <div className="grid grid-cols-2 gap-3">
          <div className="inset-panel">
            <div className="flex items-center justify-between">
              <div className="text-xs font-semibold text-slate-500">From</div>
              <div className="text-sm font-semibold tabular-nums text-slate-900">F{originFloor}</div>
            </div>
            <div className="mt-2 grid gap-1" style={{ gridTemplateColumns: `repeat(${colCount}, minmax(0, 1fr))` }}>
              {floorItems.map((floor) => (
                <Button
                  key={`from-${floor}`}
                  type="button"
                  disabled={!enabled || isBusy || floor === destinationFloor}
                  onClick={() => setOriginFloor(floor)}
                  variant={floor === originFloor ? 'primary' : 'neutral'}
                  size="sm"
                  className="h-8 px-0 text-xs"
                >
                  {floor}
                </Button>
              ))}
            </div>
          </div>

          <div className="inset-panel">
            <div className="flex items-center justify-between">
              <div className="text-xs font-semibold text-slate-500">To</div>
              <div className="text-sm font-semibold tabular-nums text-slate-900">F{destinationFloor}</div>
            </div>
            <div className="mt-2 grid gap-1" style={{ gridTemplateColumns: `repeat(${colCount}, minmax(0, 1fr))` }}>
              {floorItems.map((floor) => (
                <Button
                  key={`to-${floor}`}
                  type="button"
                  disabled={!enabled || isBusy || floor === originFloor}
                  onClick={() => setDestinationFloor(floor)}
                  variant={floor === destinationFloor ? 'primary' : 'neutral'}
                  size="sm"
                  className="h-8 px-0 text-xs"
                >
                  {floor}
                </Button>
              ))}
            </div>
          </div>
        </div>

        <div className="inset-panel">
          <div className="flex items-center justify-between text-xs font-semibold text-slate-500">
            <span>Passenger weight</span>
            <span className="tabular-nums text-slate-700">{Math.round(passengerWeight)} lb</span>
          </div>
          <input
            type="range"
            min={80}
            max={400}
            step={5}
            value={passengerWeight}
            onChange={(event) => setPassengerWeight(Number(event.target.value))}
            disabled={!enabled || isBusy}
            className="mt-2 w-full accent-blue-600"
          />
        </div>

        <Button type="submit" disabled={!enabled || isBusy || originFloor === destinationFloor} variant="primary">
          Enqueue Call
        </Button>
        {!enabled && <p className="hint-text">Manual calls are available only when manual mode is running.</p>}
      </form>
    </section>
  );
}
