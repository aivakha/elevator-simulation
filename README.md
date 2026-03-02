# Elevator Simulator v2

Production-style elevator simulation platform with real-time control UI.

## Tech Stack
- Backend: Laravel 11 (PHP 8.3), PostgreSQL, Redis
- Frontend: React 18 + TypeScript + Vite + Tailwind CSS
- Realtime: Node.js WebSocket gateway (`ws`) + Redis pub/sub
- Deployment: Railway (API, worker, websocket, frontend)

## Services
- `backend/`: API, simulation engine, Redis runtime state, lifecycle/condition controls
- `worker`: continuous tick loop (`simulation:run-loop`) for all running simulations
- `websocket/`: Redis channel fan-out to browser websocket clients
- `frontend/`: operator UI (lobby, simulation workspace, docs page)

## Core Functional Areas
- Dispatch algorithms: `nearestCar`, `scan`, `look`
- Modes: `manual`, `regular`, `morningPeak`, `eveningPeak`
- Safety: emergency recall, overload, out-of-service transitions
- Visuals: per-floor movement animation, door-state animation, condition color mapping

## Runtime Flow
1. Simulation config is persisted in PostgreSQL.
2. Runtime state is created/reset in Redis.
3. Worker advances simulation ticks continuously.
4. Tick payloads are published to Redis channels.
5. WebSocket service broadcasts ticks to connected UI clients.
6. Frontend renders live counters, car states, queues, and safety controls.

## API Highlights
- `POST /api/v1/simulations`
- `GET /api/v1/simulations`
- `POST /api/v1/simulations/{id}/start`
- `POST /api/v1/simulations/{id}/pause`
- `POST /api/v1/simulations/{id}/reset`
- `GET /api/v1/simulations/{id}/queue-preview`
- `PATCH /api/v1/simulations/{id}/condition`
- `PATCH /api/v1/simulations/{id}/elevators/{elevatorId}/condition`

## Railway Deployment
See `RAILWAY_DEPLOY.md` for service-by-service configuration.

## Repository Conventions
- Backend commits: `ELEV-BE-{ticket}: ...`
- Frontend commits: `ELEV-FE-{ticket}: ...`
- WebSocket commits: `ELEV-WS-{ticket}: ...`
- Deployment commits: `ELEV-DE-{ticket}: ...`
