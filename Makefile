.DEFAULT_GOAL := help

SHELL := /bin/bash
COMPOSE := docker compose -f docker/docker-compose.yml -p our-wedding-website
APP := $(COMPOSE) exec -T php
GRAPHVIZ := docker run --rm -i docker.io/minidocks/graphviz dot -Tsvg
DEVELOPMENT_IMAGE := ghcr.io/eddmann/our-wedding-website:dev-7ca6fed

##@ Setup

.PHONY: start
start: export EVENT_STORE_BACKEND=Postgres
start: export PROJECTION_BACKEND=Postgres
start: up composer yarn db test-db ## Boots the application in development mode (with Postgres ES and projections)

.PHONY: start-dynamodb
start-dynamodb: export EVENT_STORE_BACKEND=DynamoDb
start-dynamodb: export PROJECTION_BACKEND=DynamoDb
start-dynamodb: up composer yarn db test-db ## Boots the application in development mode (with DynamoDB ES and projections)

up:
	$(COMPOSE) build
	$(COMPOSE) up -d

.PHONY: stop
stop: ## Stop and clean-up the application (remove containers, networks, images, and volumes)
	$(COMPOSE) down -v --remove-orphans

.PHONY: restart ## Restart the application in development mode
restart: stop start

.PHONY: composer
composer: ## Installs the latest Composer dependencies within running instance
  ifdef GITHUB_TOKEN
	@echo "Found GitHub access token, configuring composer"
	@$(APP) composer config -g http-basic.github.com x-access-token ${GITHUB_TOKEN}
  endif
	$(APP) composer install --no-interaction --no-ansi

.PHONY: yarn
yarn: ## Installs and builds the latest Yarn dependencies within running instance
	$(APP) bash -c "yarn && yarn build"

.PHONY: encore
encore: yarn ## Watches for CSS/JS changes and auto-transpiles them
	$(APP) yarn watch

.PHONY: db
db: ## (Re)creates the development database (with migrations)
	$(APP) bin/console doctrine:database:drop --force --if-exists
	$(APP) bin/console doctrine:database:create -n
	$(APP) bin/console doctrine:migrations:migrate -n --allow-no-migration
	$(APP) bin/console dynamodb:create-schema --force

.PHONY: test-db
test-db: ## (Re)creates the test database (with migrations)
	$(APP) bin/console doctrine:database:drop --force --if-exists --env=test
	$(APP) bin/console doctrine:database:create -n --env=test
	$(APP) bin/console doctrine:migrations:migrate -n --allow-no-migration --quiet --env=test
	$(APP) bin/console dynamodb:create-schema --force --env=test

.PHONY: clean
clean: ## Remove all untracked/changed files
	@git clean -ffdx .

##@ Release

.PHONY: build
build: _require_ARTIFACT_PATH ## Build and package the app for deployment
	docker run --rm \
	  -v $(PWD)/app:/var/task \
	  -v $(PWD)/app/var/cache:/tmp/cache \
	  -e APP_ENV=prod \
	  -e APP_SECRET= \
	  -e ADMIN_PASSWORD= \
	  ${DEVELOPMENT_IMAGE} \
	  bash -c "([ -z ${GITHUB_TOKEN} ] || composer config -g github-oauth.github.com ${GITHUB_TOKEN}); \
	           yarn && \
	           composer install --no-dev --no-interaction --no-ansi --classmap-authoritative --no-scripts && \
	           yarn build && \
	           bin/console cache:clear --no-debug --no-interaction"
	tar --create --gzip --exclude=node_modules --file ${ARTIFACT_PATH} app/

.PHONY: deploy
deploy: _require_AWS_ACCESS_KEY_ID _require_AWS_SECRET_ACCESS_KEY _require_ARTIFACT_PATH _require_STAGE ## Unpack and deploy the app within stage environment
	rm -fr app/ && tar -xf ${ARTIFACT_PATH}
	mv app/php/conf.d/preload.ini.dist app/php/conf.d/preload.ini
	docker run --rm \
	  -v $(PWD)/app/public/build:/build \
	  -e AWS_ACCESS_KEY_ID \
	  -e AWS_SECRET_ACCESS_KEY \
	  --entrypoint= \
	  docker.io/amazon/aws-cli:2.4.6 \
	    bash -c "ASSETS_S3_BUCKET_NAME=$$(aws ssm get-parameter --region=eu-west-1 --name /our-wedding/${STAGE}/apps/website/assets-s3-bucket-name --query Parameter.Value --output text); \
	             aws s3 sync --region=eu-west-1 /build "s3://\$${ASSETS_S3_BUCKET_NAME}/build" --quiet --delete --sse AES256"
	docker run --rm \
	  -v $(PWD)/app:/var/task \
	  -e AWS_ACCESS_KEY_ID \
	  -e AWS_SECRET_ACCESS_KEY \
	  ${DEVELOPMENT_IMAGE} \
	    serverless deploy --stage ${STAGE} --region eu-west-1 --verbose --conceal

.PHONY: deploy/db-migrate
deploy/db-migrate: _require_AWS_ACCESS_KEY_ID _require_AWS_SECRET_ACCESS_KEY _require_STAGE ## Apply outstanding migrations to stage environment database
	docker run --rm \
	  -v $(PWD)/app:/var/task \
	  -e AWS_ACCESS_KEY_ID \
	  -e AWS_SECRET_ACCESS_KEY \
	  ${DEVELOPMENT_IMAGE} \
	    vendor/bin/bref cli our-wedding-website-${STAGE}-console --region eu-west-1 -- doctrine:migrations:migrate -n

##@ Testing/Linting

.PHONY: can-release
can-release: security lint test ## Execute all the checks run by CI to ensure the application can be released

.PHONY: security
security: ## Checks if we are running any dependencies with known security vulnerabilities
	$(APP) local-php-security-checker

.PHONY: lint
lint: ## Runs the lint tools we have configured for the application
	$(APP) composer validate --strict
	$(APP) php-cs-fixer fix --dry-run --diff
	$(APP) deptrac --no-interaction --no-progress
	$(APP) psalm --no-progress --monochrome --show-info=true --threads=4 --diff
	$(APP) yarn lint

.PHONY: test
test: test-db ## Runs the entire test-suite (test/* for specific filter)
	$(APP) bin/phpunit

test/%:
	$(APP) bin/phpunit --filter $*

.PHONY: test-domain
test-domain: ## Runs the domain tests
	$(APP) bin/phpunit --testsuite=domain

.PHONY: test-application
test-application: ## Runs the application tests
	$(APP) bin/phpunit --testsuite=application

.PHONY: test-infrastructure
test-infrastructure: test-db ## Runs the infrastructure tests
	$(APP) bin/phpunit --testsuite=infrastructure

.PHONY: test-ui
test-ui: test-db ## Runs the ui tests
	$(APP) bin/phpunit --testsuite=ui

.PHONY: cs-fix
cs-fix: ## Auto-fixes any code-styling related code violations
	$(APP) php-cs-fixer fix
	$(APP) yarn prettier

.PHONY: update-snapshots
update-snapshots: ## Updates event store snapshots that are mismatches
	$(APP) bash -c "UPDATE_EVENT_STORE_SNAPSHOT_MISMATCHES=true bin/phpunit --testsuite=application"

.PHONY: documentation
documentation: ## (Re)generates the dynamic event diagrams and message listings
	@echo "Generating documentation..."
	@$(APP) bash -c "bin/console documentation:command-diagram" | $(GRAPHVIZ) > documentation/command-digram.svg
	@$(APP) bash -c "bin/console documentation:aggregate-event-diagram" | $(GRAPHVIZ) > documentation/aggregate-event-digram.svg
	@$(APP) bash -c "bin/console documentation:message-list" > documentation/message-list.txt

##@ Running Instance

.PHONY: shell
shell: ## Provides shell access to the running PHP container instance
	$(COMPOSE) exec php bash

.PHONY: logs
logs: ## Tails all container logs
	$(COMPOSE) logs -f

.PHONY: ps
ps: ## List all running containers
	$(COMPOSE) ps

.PHONY: open-web
open-web: ## Opens the website in the default browser
	open "http://0.0.0.0:8080"

.PHONY: open-mailhog
open-mailhog: ## Opens the MailHog web interface in the default browser
	open "http://0.0.0.0:8025"

.PHONY: psql
psql: ## Open a Postgres client session to the development database
	$(COMPOSE) exec postgres psql -U user db

_require_%:
	@_=$(or $($*),$(error "`$*` env var required"))

# https://blog.thapaliya.com/posts/well-documented-makefiles/
.PHONY: help
help:
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
