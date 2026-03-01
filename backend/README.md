# Elevator v2 Backend

Laravel 11 API backend for the new elevator simulator.

## Principles
- No auth/user relations.
- Service-layer business logic in `app/Modules/Simulation`.
- Enum-driven domain states.
- Redis runtime state + pub/sub tick channels.
- PostgreSQL persistence for simulations, runs, and events.

## Main API routes
- `POST /api/v1/simulations`
- `GET /api/v1/simulations`
- `POST /api/v1/simulations/{id}/start`
- `POST /api/v1/simulations/{id}/pause`
- `POST /api/v1/simulations/{id}/reset`
- `GET /api/v1/simulations/{id}/queue-preview`
- `PATCH /api/v1/simulations/{id}/condition`
- `PATCH /api/v1/simulations/{id}/elevators/{elevatorId}/condition`

## Worker command
- `php artisan simulation:run-loop --intervalMs=1000`
