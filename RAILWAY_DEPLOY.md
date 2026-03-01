# Railway Deployment (Ready-to-Use)

This repo is now prepared for Railway with dedicated Dockerfiles:

- `backend/Dockerfile.railway`
- `frontend/Dockerfile.railway`
- `websocket/Dockerfile.railway`

Plus startup scripts:

- `backend/railway/start-api.sh`
- `backend/railway/start-worker.sh`
- `frontend/railway/start-frontend.sh`

## 1. Create Railway services

Create these services in one Railway project:

1. `backend-api` (from this repo)
2. `backend-worker` (from this repo)
3. `websocket` (from this repo)
4. `frontend` (from this repo)
5. `postgres` (Railway PostgreSQL)
6. `redis` (Railway Redis)

## 2. Configure source paths per service

Use the same repo for all app services, with these settings:

1. `backend-api`
- Root directory: `backend`
- Dockerfile path: `Dockerfile.railway`
- Start command: leave default (uses `/app/railway/start-api.sh`)

2. `backend-worker`
- Root directory: `backend`
- Dockerfile path: `Dockerfile.worker.railway`
- Start command: leave empty (worker loop starts automatically via image CMD)

3. `websocket`
- Root directory: `websocket`
- Dockerfile path: `Dockerfile.railway`

4. `frontend`
- Root directory: `frontend`
- Dockerfile path: `Dockerfile.railway`

## 3. Environment variables

Set these in `backend-api` and `backend-worker`:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://<your-frontend-domain>`
- `LOG_CHANNEL=stack`
- `LOG_LEVEL=info`
- `DB_CONNECTION=pgsql`
- `DB_HOST=<postgres host from Railway>`
- `DB_PORT=<postgres port>`
- `DB_DATABASE=<postgres db>`
- `DB_USERNAME=<postgres user>`
- `DB_PASSWORD=<postgres password>`
- `REDIS_CLIENT=predis`
- `REDIS_HOST=<redis host from Railway>`
- `REDIS_PORT=<redis port>`
- `REDIS_PASSWORD=<redis password if set>`
- `CACHE_STORE=redis`
- `SESSION_DRIVER=file`
- `QUEUE_CONNECTION=sync`

Optional:
- `APP_KEY=<base64 key>` (if omitted, API start script generates one at runtime)
- `SIMULATION_TICK_INTERVAL_MS=1000` (worker only)

Set these in `websocket`:

- `REDIS_HOST=<redis host from Railway>`
- `REDIS_PORT=<redis port>`
- `REDIS_PASSWORD=<redis password if set>`

`websocket` listens on Railway `PORT` automatically.

Set these in `frontend`:

- `BACKEND_URL=https://<backend-api public Railway URL>`
- `WEBSOCKET_URL=https://<websocket public Railway URL>`

The frontend service reverse-proxies:
- `/api/*` -> `${BACKEND_URL}`
- `/ws` -> `${WEBSOCKET_URL}`

## 4. Deploy order

1. Deploy `postgres` and `redis`.
2. Deploy `backend-api`.
3. Deploy `backend-worker`.
4. Deploy `websocket`.
5. Deploy `frontend`.

## 5. Post-deploy checks

1. Open frontend URL and create/start a simulation.
2. Confirm header websocket indicator turns green.
3. Confirm ticks advance (queue/positions changing).
4. Confirm worker logs show `simulation:run-loop` running.
