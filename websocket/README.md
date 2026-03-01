# WebSocket Gateway

Node.js service that subscribes to Redis tick channels and broadcasts updates to browser clients.

## Responsibilities
- Subscribe to simulation tick channels in Redis.
- Fan out tick payloads to all connected websocket clients.
- Keep frontend live state in sync with backend runtime ticks.

## Channel Pattern
- `simulation:*:ticks`
- `*simulation:*:ticks`

## Environment Variables
- `PORT`: listening port (Railway default), fallback to `WS_PORT` then `8090`
- `REDIS_URL`: optional full Redis URL (preferred when available)
- `REDIS_HOST`: Redis host (used when `REDIS_URL` is not set)
- `REDIS_PORT`: Redis port
- `REDIS_PASSWORD`: Redis password for authenticated instances

## Notes
- Service supports authenticated Redis deployments.
- Redis publisher/subscriber errors are logged explicitly.
