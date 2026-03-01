import type {
  CreateSimulationInput,
  ElevatorConditionControl,
  QueuePreview,
  SimulationConditionControl,
  SimulationConfigOptions,
  SimulationSummary,
} from '../model/types';

const apiBaseUrl = '/api/v1';
const jsonHeaders = { 'Content-Type': 'application/json' };

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: unknown;
};

async function apiRequest(path: string, options: RequestOptions = {}): Promise<Response> {
  const { method = 'GET', body } = options;

  return fetch(`${apiBaseUrl}${path}`, {
    method,
    headers: body === undefined ? undefined : jsonHeaders,
    body: body === undefined ? undefined : JSON.stringify(body),
  });
}

async function expectOk(response: Response, message: string): Promise<Response> {
  if (!response.ok) {
    throw new Error(message);
  }

  return response;
}

async function getJson<T>(path: string, message: string): Promise<T> {
  const response = await apiRequest(path);
  await expectOk(response, message);
  return await response.json() as Promise<T>;
}

async function postJson<TResponse>(path: string, body: unknown, message: string): Promise<TResponse> {
  const response = await apiRequest(path, { method: 'POST', body });
  await expectOk(response, message);
  return await response.json() as Promise<TResponse>;
}

async function postVoid(path: string, body: unknown | undefined, message: string): Promise<void> {
  const response = await apiRequest(path, { method: 'POST', body });
  await expectOk(response, message);
}

async function patchVoid(path: string, body: unknown, message: string): Promise<void> {
  const response = await apiRequest(path, { method: 'PATCH', body });
  await expectOk(response, message);
}

async function deleteVoid(path: string, message: string): Promise<void> {
  const response = await apiRequest(path, { method: 'DELETE' });
  await expectOk(response, message);
}

export async function listSimulations(): Promise<SimulationSummary[]> {
  const payload = await getJson<{ simulations: SimulationSummary[] }>(
    '/simulations',
    'failed to list simulations',
  );

  return payload.simulations;
}

export async function fetchSimulationConfigOptions(): Promise<SimulationConfigOptions> {
  return getJson<SimulationConfigOptions>(
    '/simulations/config/options',
    'failed to load simulation config options',
  );
}

export async function createSimulation(input: CreateSimulationInput): Promise<SimulationSummary> {
  const response = await apiRequest('/simulations', {
    method: 'POST',
    body: input,
  });

  if (!response.ok && response.status !== 409) {
    throw new Error('failed to create simulation');
  }

  if (response.status === 409) {
    const simulations = await listSimulations();
    const existing = simulations.find((item) => item.id === input.id);

    if (!existing) {
      throw new Error('simulation id conflict');
    }

    return existing;
  }

  const payload = (await response.json()) as { simulation: SimulationSummary };
  return payload.simulation;
}

export async function updateSimulationConfig(
  simulationId: string,
  input: CreateSimulationInput,
): Promise<SimulationSummary> {
  const payload = await postJson<{ simulation: SimulationSummary }>(
    `/simulations/${simulationId}/config`,
    input,
    'failed to update simulation config',
  );

  return payload.simulation;
}

export async function deleteSimulation(simulationId: string): Promise<void> {
  await deleteVoid(`/simulations/${simulationId}`, 'failed to delete simulation');
}

export async function fetchQueuePreview(simulationId: string): Promise<QueuePreview> {
  const response = await apiRequest(`/simulations/${simulationId}/queue-preview`);

  if (!response.ok) {
    throw new Error(`queue preview fetch failed with status ${response.status}`);
  }

  return await response.json() as Promise<QueuePreview>;
}

export async function lifecycleAction(simulationId: string, action: 'start' | 'pause' | 'reset'): Promise<void> {
  await postVoid(
    `/simulations/${simulationId}/${action}`,
    undefined,
    `failed to execute lifecycle action ${action}`,
  );
}

export async function enqueueManualCall(
  simulationId: string,
  originFloor: number,
  destinationFloor: number,
  passengerWeight: number,
): Promise<void> {
  await postVoid(
    `/simulations/${simulationId}/calls/manual`,
    { originFloor, destinationFloor, passengerWeight },
    'failed to enqueue manual call',
  );
}

export async function setSimulationCondition(
  simulationId: string,
  condition: SimulationConditionControl,
): Promise<void> {
  await patchVoid(
    `/simulations/${simulationId}/condition`,
    { condition },
    'failed to set simulation condition',
  );
}

export async function setElevatorCondition(
  simulationId: string,
  elevatorId: string,
  condition: ElevatorConditionControl,
): Promise<void> {
  await patchVoid(
    `/simulations/${simulationId}/elevators/${encodeURIComponent(elevatorId)}/condition`,
    { condition },
    'failed to set elevator condition',
  );
}
