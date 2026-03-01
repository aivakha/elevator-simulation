#!/usr/bin/env sh
set -eu

: "${BACKEND_URL:?BACKEND_URL is required}"
: "${WEBSOCKET_URL:?WEBSOCKET_URL is required}"

envsubst '${BACKEND_URL} ${WEBSOCKET_URL}' \
  < /etc/nginx/templates/default.conf.template \
  > /etc/nginx/conf.d/default.conf

exec nginx -g 'daemon off;'
