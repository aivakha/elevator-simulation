import { useMemo, useState } from 'react';
import { simulationStatusPill, toTitleCase } from '../lib/format';
import type {
  CreateSimulationInput,
  DispatchAlgorithm,
  SimulationConfigOptions,
  SimulationMode,
  SimulationSummary,
} from '../../model/types';
import { Button } from './Button';

type SimulationLobbyProps = {
  simulations: SimulationSummary[];
  configOptions: SimulationConfigOptions | null;
  isBusy: boolean;
  onOpenDocumentation: () => void;
  onOpenSimulation: (simulationId: string) => void;
  onCreateSimulation: (input: CreateSimulationInput) => Promise<void>;
  onUpdateSimulationConfig: (simulationId: string, input: CreateSimulationInput) => Promise<void>;
  onDeleteSimulation: (simulationId: string) => Promise<void>;
};

type LobbyView = 'list' | 'create' | 'edit';

const modes: SimulationMode[] = ['manual', 'regular', 'morningPeak', 'eveningPeak'];
const algorithms: DispatchAlgorithm[] = ['nearestCar', 'scan', 'look'];

export function SimulationLobby({
  simulations,
  configOptions,
  isBusy,
  onOpenDocumentation,
  onOpenSimulation,
  onCreateSimulation,
  onUpdateSimulationConfig,
  onDeleteSimulation,
}: SimulationLobbyProps) {
  const [view, setView] = useState<LobbyView>('list');
  const [editingSimulationId, setEditingSimulationId] = useState<string | null>(null);
  const [name, setName] = useState('New Simulation');
  const [floors, setFloors] = useState(0);
  const [elevators, setElevators] = useState(0);
  const [capacityPerElevator, setCapacityPerElevator] = useState(0);
  const [doorOpenSeconds, setDoorOpenSeconds] = useState(0);
  const [mode, setMode] = useState<SimulationMode>('regular');
  const [algorithm, setAlgorithm] = useState<DispatchAlgorithm>('scan');

  const editingSimulation = useMemo(
    () => simulations.find((simulation) => simulation.id === editingSimulationId) ?? null,
    [simulations, editingSimulationId],
  );
  const limits = configOptions?.limits ?? null;

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const payload: CreateSimulationInput = {
      name,
      floors,
      elevators,
      capacityPerElevator,
      doorOpenSeconds,
      mode,
      algorithm,
    };

    if (view === 'edit' && editingSimulationId !== null) {
      await onUpdateSimulationConfig(editingSimulationId, payload);
      setView('list');
      setEditingSimulationId(null);
      return;
    }

    await onCreateSimulation(payload);
  }

  function openCreateForm() {
    if (configOptions === null) {
      return;
    }
    const defaults = configOptions.defaults;

    setView('create');
    setEditingSimulationId(null);
    setName(defaults.name);
    setFloors(defaults.floors);
    setElevators(defaults.elevators);
    setCapacityPerElevator(defaults.capacityPerElevator);
    setDoorOpenSeconds(defaults.doorOpenSeconds);
    setMode(defaults.mode);
    setAlgorithm(defaults.algorithm);
  }

  function openEditForm(simulation: SimulationSummary) {
    setView('edit');
    setEditingSimulationId(simulation.id);
    setName(simulation.name);
    setFloors(simulation.config_json.floors);
    setElevators(simulation.config_json.elevators);
    setCapacityPerElevator(simulation.config_json.capacityPerElevator);
    setDoorOpenSeconds(simulation.config_json.doorOpenSeconds);
    setMode(simulation.config_json.mode);
    setAlgorithm(simulation.config_json.algorithm);
  }

  function formatCreatedDate(simulation: SimulationSummary): string {
    const created = simulation.created_at;
    if (typeof created !== 'string' || created.length === 0) {
      return 'unknown';
    }

    return new Date(created).toLocaleDateString();
  }

  function formatModeLabel(modeValue: SimulationMode): string {
    return toTitleCase(modeValue);
  }

  function formatAlgorithmLabel(algorithmValue: DispatchAlgorithm): string {
    return toTitleCase(algorithmValue);
  }

  if (view === 'list') {
    const { runningCount, pausedCount, draftCount } = simulations.reduce(
      (acc, item) => {
        if (item.status === 'running') acc.runningCount++;
        else if (item.status === 'paused') acc.pausedCount++;
        else if (item.status === 'draft') acc.draftCount++;
        return acc;
      },
      { runningCount: 0, pausedCount: 0, draftCount: 0 },
    );

    return (
      <main className="lobby-main">
        <div className="lobby-container">
          <section className="lobby-hero-card">
            <div className="lobby-hero-row">
              <div>
                <p className="lobby-overline">Elevator Simulator</p>
                <h1 className="lobby-title">Simulations</h1>
                <p className="lobby-subtitle">Create, open, and manage scenarios from one place.</p>
              </div>
              <div className="lobby-hero-actions">
                <Button
                  type="button"
                  className="lobby-docs-button"
                  disabled={isBusy}
                  onClick={onOpenDocumentation}
                >
                  Documentation
                </Button>
                <Button
                  type="button"
                  variant="primary"
                  className="lobby-create-button"
                  disabled={isBusy || configOptions === null}
                  onClick={openCreateForm}
                >
                  New Simulation
                </Button>
              </div>
            </div>

            <div className="lobby-stats-grid">
              <div className="lobby-stat-card lobby-stat-card--total">
                <div className="lobby-stat-title">Total</div>
                <div className="lobby-stat-number">{simulations.length}</div>
              </div>
              <div className="lobby-stat-card lobby-stat-card--running">
                <div className="lobby-stat-title">Running</div>
                <div className="lobby-stat-number">{runningCount}</div>
              </div>
              <div className="lobby-stat-card lobby-stat-card--paused">
                <div className="lobby-stat-title">Paused</div>
                <div className="lobby-stat-number">{pausedCount}</div>
              </div>
              <div className="lobby-stat-card lobby-stat-card--draft">
                <div className="lobby-stat-title">Draft</div>
                <div className="lobby-stat-number">{draftCount}</div>
              </div>
            </div>
          </section>

          <section className="lobby-list-section">
            {simulations.length === 0 && (
              <div className="lobby-empty">No simulations created yet.</div>
            )}

            {simulations.map((simulation) => (
              <article
                key={simulation.id}
                className="sim-list-row"
              >
                <div className="sim-list-row-layout">
                  <button
                    type="button"
                    className="lobby-open-button"
                    onClick={() => void onOpenSimulation(simulation.id)}
                    disabled={isBusy}
                  >
                    <h2 className="lobby-row-title">{simulation.name}</h2>
                    <p className="lobby-created-date">Created {formatCreatedDate(simulation)}</p>
                    <div className="lobby-tags">
                      <span className="sim-tag">Floors {simulation.config_json.floors}</span>
                      <span className="sim-tag">Cars {simulation.config_json.elevators}</span>
                      <span className="sim-tag">Mode {toTitleCase(simulation.config_json.mode)}</span>
                      <span className="sim-tag">Algo {toTitleCase(simulation.config_json.algorithm)}</span>
                    </div>
                  </button>

                  <div className="lobby-actions">
                    <span className={`lobby-status-pill ${simulationStatusPill(simulation.status).className}`}>
                      {simulationStatusPill(simulation.status).label}
                    </span>

                    <button
                      type="button"
                      aria-label="Edit simulation"
                      className="btn-row-action btn-row-action--edit"
                      disabled={isBusy}
                      onClick={() => openEditForm(simulation)}
                    >
                      Edit
                    </button>

                    <button
                      type="button"
                      aria-label="Delete simulation"
                      className="btn-row-action btn-row-action--delete"
                      disabled={isBusy}
                      onClick={() => void onDeleteSimulation(simulation.id)}
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </article>
            ))}
          </section>
        </div>
      </main>
    );
  }

  return (
    <main className="lobby-form-main">
      <div className="lobby-form-card">
        <div className="lobby-form-header">
          <div className="lobby-form-heading">
            <p className="lobby-form-overline">Simulation Setup</p>
            <h1 className="lobby-form-title">
              {view === 'create' ? 'Create Simulation' : `Edit ${editingSimulation?.name ?? 'Simulation'}`}
            </h1>
            <p className="lobby-form-subtitle">Define building and dispatch settings in one place.</p>
          </div>
          <Button type="button" className="lobby-back-button" disabled={isBusy} onClick={() => setView('list')}>
            Back
          </Button>
        </div>

        {configOptions === null || limits === null ? (
          <div className="lobby-loading-state">Loading configuration...</div>
        ) : (
          <form onSubmit={handleSubmit} className="lobby-form">
            <label className="form-label">
              Name
              <input
                className="form-input"
                value={name}
                onChange={(event) => setName(event.target.value)}
                disabled={isBusy}
              />
            </label>

            <div className="lobby-form-grid">
              <label className="form-label">
                Floors (max {limits.maxFloors})
                <input
                  className="form-input"
                  type="number"
                  min={limits.minFloors}
                  max={limits.maxFloors}
                  value={floors}
                  onChange={(event) => setFloors(Number(event.target.value))}
                  disabled={isBusy}
                />
              </label>

              <label className="form-label">
                Cars (max {limits.maxElevators})
                <input
                  className="form-input"
                  type="number"
                  min={limits.minElevators}
                  max={limits.maxElevators}
                  value={elevators}
                  onChange={(event) => setElevators(Number(event.target.value))}
                  disabled={isBusy}
                />
              </label>
            </div>

            <label className="form-label">
              Capacity (lb)
              <input
                className="form-input"
                type="number"
                min={limits.minCapacityPerElevator}
                max={limits.maxCapacityPerElevator}
                step={100}
                value={capacityPerElevator}
                onChange={(event) => setCapacityPerElevator(Number(event.target.value))}
                disabled={isBusy}
              />
            </label>

            <label className="form-label">
              Door (seconds)
              <input
                className="form-input"
                type="number"
                min={limits.minDoorOpenSeconds}
                max={limits.maxDoorOpenSeconds}
                value={doorOpenSeconds}
                onChange={(event) => setDoorOpenSeconds(Number(event.target.value))}
                disabled={isBusy}
              />
            </label>

            <div className="lobby-form-grid">
              <label className="form-label">
                Mode
                <select
                  className="form-input"
                  value={mode}
                  onChange={(event) => setMode(event.target.value as SimulationMode)}
                  disabled={isBusy}
                >
                  {modes.map((entry) => (
                    <option key={entry} value={entry}>{formatModeLabel(entry)}</option>
                  ))}
                </select>
              </label>

              <label className="form-label">
                Algorithm
                <select
                  className="form-input"
                  value={algorithm}
                  onChange={(event) => setAlgorithm(event.target.value as DispatchAlgorithm)}
                  disabled={isBusy}
                >
                  {algorithms.map((entry) => (
                    <option key={entry} value={entry}>{formatAlgorithmLabel(entry)}</option>
                  ))}
                </select>
              </label>
            </div>

            <Button type="submit" variant="primary" className="lobby-submit-button" disabled={isBusy}>
              {view === 'create' ? 'Create And Open' : 'Save Configuration'}
            </Button>
          </form>
        )}
      </div>
    </main>
  );
}
