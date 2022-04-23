#!/bin/sh

if [ "$@" != "" ]; then
  exec "$@";
elif [ "$CONTAINER_ROLE" = "worker" ]; then
  exec /srv/app/bin/console messenger:consume async -vv --time-limit=3600;
elif [ "$CONTAINER_ROLE" = "web" ]; then
  exec /usr/bin/caddy run -config /etc/Caddyfile;
elif [ "$CONTAINER_ROLE" = "migrate-db" ]; then
  exec /srv/app/bin/console doctrine:migrations:migrate -n --allow-no-migration;
else
  exit 1;
fi
