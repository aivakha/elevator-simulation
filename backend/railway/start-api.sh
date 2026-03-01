#!/usr/bin/env sh
set -eu

cd /app

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

ATTEMPTS=0
MAX_ATTEMPTS=30

until php artisan migrate --force >/dev/null 2>&1; do
  ATTEMPTS=$((ATTEMPTS + 1))
  if [ "$ATTEMPTS" -ge "$MAX_ATTEMPTS" ]; then
    echo "migrations failed after ${MAX_ATTEMPTS} attempts"
    exit 1
  fi
  echo "waiting for database... (${ATTEMPTS}/${MAX_ATTEMPTS})"
  sleep 2
done

echo "starting backend on port ${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
