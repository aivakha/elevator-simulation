type RunStatsPanelProps = {
  waitingCount: number;
  pickedUpCount: number;
  droppedOffCount: number;
  totalCount: number;
};

function MetricPill({ label, value }: { label: string; value: string }) {
  return (
    <div className="metric-pill">
      <span className="data-pill-label">{label}</span>
      <span className="data-pill-value">{value}</span>
    </div>
  );
}

export function RunStatsPanel({ waitingCount, pickedUpCount, droppedOffCount, totalCount }: RunStatsPanelProps) {
  return (
    <div className="flex flex-col gap-1">
      <div className="flex flex-wrap items-center gap-2">
        <MetricPill label="Unassigned" value={String(waitingCount)} />
        <MetricPill label="Picked Up" value={String(pickedUpCount)} />
        <MetricPill label="Dropped Off" value={String(droppedOffCount)} />
        <MetricPill label="Total" value={String(totalCount)} />
      </div>
    </div>
  );
}
