#!/usr/bin/env sh
set -eu

cd /app

echo "starting simulation worker with interval ${SIMULATION_TICK_INTERVAL_MS:-1000} ms"
exec php artisan simulation:run-loop --intervalMs="${SIMULATION_TICK_INTERVAL_MS:-1000}"
