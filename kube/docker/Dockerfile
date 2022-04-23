FROM docker.io/caddy:2.4.6-builder-alpine AS caddy

RUN xcaddy build --with github.com/baldinof/caddy-supervisor

FROM docker.io/php:8.1.4-fpm-alpine3.15 AS php

# Caddy
COPY --from=caddy /usr/bin/caddy /usr/bin/caddy
COPY kube/docker/Caddyfile /etc/Caddyfile

# PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/bin/
RUN chmod +x /usr/bin/install-php-extensions \
  && install-php-extensions opcache pdo pdo_pgsql redis amqp intl apcu \
  && rm /usr/bin/install-php-extensions

# Composer
COPY --from=docker.io/composer:2.3.4 /usr/bin/composer /usr/bin/

# Entrypoint
COPY kube/docker/docker-entrypoint.sh /docker-entrypoint.sh
ENTRYPOINT ["/docker-entrypoint.sh"]

WORKDIR /srv/app

FROM docker.io/node:16.14.2-alpine3.15 AS assets

COPY app/ /srv/app/

RUN cd /srv/app && yarn && yarn build

FROM php AS prod

LABEL org.opencontainers.image.source=https://github.com/eddmann/our-wedding-website

ENV APP_ENV=prod

COPY app/ /srv/app/
ADD kube/docker/redis.yaml /srv/app/config/packages/prod/redis.yaml
COPY --from=assets /srv/app/public/build /srv/app/public/build

RUN composer install --no-dev --no-interaction --no-ansi --classmap-authoritative --no-scripts \
 && rm /usr/bin/composer \
 && bin/console cache:clear --no-debug --no-interaction \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
