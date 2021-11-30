.DEFAULT_GOAL := help

SHELL := /bin/bash
COMPOSE := docker-compose -f docker/docker-compose.yml -p our-wedding
APP := $(COMPOSE) exec -T php
GRAPHVIZ := docker run --rm -i docker.io/minidocks/graphviz dot -Tsvg

##@ Setup

.PHONY: start
start: up composer yarn db test-db ## Boots the application in development mode

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
	$(APP) composer install --ignore-platform-reqs --no-interaction --no-ansi
	$(APP) bin/phpunit --version # ensure PHPUnit is installed

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

.PHONY: test-db
test-db: ## (Re)creates the test database (with migrations)
	$(APP) bin/console doctrine:database:drop --force --if-exists --env=test
	$(APP) bin/console doctrine:database:create -n --env=test
	$(APP) bin/console doctrine:migrations:migrate -n --allow-no-migration --quiet --env=test

.PHONY: clean
clean: ## Remove all untracked/changed files
	@git clean -ffdx .

##@ Testing/Linting

.PHONY: can-release
can-release: security lint test ## Execute all the checks run by CI to ensure the application can be released

.PHONY: security
security: ## Checks if we are running any dependencies with known security vulnerabilities
	$(APP) local-php-security-checker

.PHONY: lint
lint: ## Runs the lint tools we have configured for the application
	$(COMPOSE) exec -e PHP_CS_FIXER_IGNORE_ENV=1 -T php php-cs-fixer fix --dry-run --diff
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
	$(COMPOSE) exec -e PHP_CS_FIXER_IGNORE_ENV=1 -T php php-cs-fixer fix
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

.PHONY: psql
psql: ## Open a Postgres client session to the development database
	$(COMPOSE) exec postgres psql -U user db

_require_%:
	@_=$(or $($*),$(error "`$*` env var required"))

# https://blog.thapaliya.com/posts/well-documented-makefiles/
.PHONY: help
help:
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
