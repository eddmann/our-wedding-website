FROM docker.io/bref/php-81-fpm-dev:1.7.7

# PHP Tooling
COPY --from=docker.io/jakzal/phpqa:1.78.0-php8.1-alpine \
  /tools/deptrac \
  /tools/php-cs-fixer \
  /tools/local-php-security-checker \
  /usr/bin/
RUN curl -sL https://github.com/vimeo/psalm/releases/download/4.27.0/psalm.phar -o /usr/bin/psalm \
 && chmod +x /usr/bin/psalm

# Composer
COPY --from=docker.io/composer:2.4.2 /usr/bin/composer /usr/bin/

# Node/Yarn
RUN curl -sL https://rpm.nodesource.com/setup_16.x | bash - \
 && curl -sL https://dl.yarnpkg.com/rpm/yarn.repo -o /etc/yum.repos.d/yarn.repo \
 && yum install nodejs yarn -y

# Serverless
RUN curl -sL https://github.com/serverless/serverless/releases/download/v3.22.0/serverless-linux-x64 -o /usr/bin/serverless \
 && chmod +x /usr/bin/serverless
