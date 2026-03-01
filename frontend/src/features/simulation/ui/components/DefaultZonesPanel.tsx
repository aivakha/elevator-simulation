import type { ElevatorZone } from '../../model/types';

type DefaultZonesPanelProps = {
  zones: ElevatorZone[];
};

export function DefaultZonesPanel({ zones }: DefaultZonesPanelProps) {
  return (
    <section className="sim-card">
      <h3 className="sim-section-title">Default Zones</h3>
      <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        {zones.map((zone) => (
          <div key={zone.elevatorId} className="zone-row">
            <span className="font-semibold text-slate-900">{zone.elevatorId}</span>
            <span className="ml-2 text-slate-600">F{zone.zoneStart} – F{zone.zoneEnd}</span>
          </div>
        ))}
      </div>
    </section>
  );
}
