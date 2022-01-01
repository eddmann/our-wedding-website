FROM docker.io/bref/php-81-fpm-dev:1.4.2

USER root

# PHP Tooling
COPY --from=docker.io/jakzal/phpqa:1.64.2-php8.0-alpine \
  /tools/deptrac \
  /tools/php-cs-fixer \
  /tools/local-php-security-checker \
  /usr/bin/
RUN curl -sL https://github.com/vimeo/psalm/releases/download/4.16.1/psalm.phar -o /usr/bin/psalm \
 && chmod +x /usr/bin/psalm

# Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/.composer
COPY --from=docker.io/composer:2.2.2 /usr/bin/composer /usr/bin/

# Node/Yarn
RUN curl -sL https://rpm.nodesource.com/setup_16.x | bash -
RUN curl -sL https://dl.yarnpkg.com/rpm/yarn.repo -o /etc/yum.repos.d/yarn.repo
RUN yum install nodejs yarn -y

# Serverless
RUN curl -sL https://github.com/serverless/serverless/releases/download/v2.70.0/serverless-linux-x64 -o /usr/bin/serverless \
 && chmod +x /usr/bin/serverless

# PHP-FPM
COPY docker/php-fpm.conf /opt/bref/etc/php-fpm.conf
CMD /opt/bin/php-fpm \
  --nodaemonize \
  --fpm-config /opt/bref/etc/php-fpm.conf \
  -d opcache.validate_timestamps=1 \
  --force-stderr \
  --allow-to-run-as-root
