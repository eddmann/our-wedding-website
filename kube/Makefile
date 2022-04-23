.DEFAULT_GOAL := help

SHELL := /bin/bash
COMPOSE := docker compose -f docker/docker-compose.yml -p our-wedding-website

##@ Setup

.PHONY: start
start: ## Boots the application
	cp docker/.dockerignore ../.dockerignore
	$(COMPOSE) up -d --build --force-recreate
	rm ../.dockerignore

.PHONY: stop
stop: ## Stop and clean-up the application (remove containers, networks, images, and volumes)
	$(COMPOSE) down -v --remove-orphans

.PHONY: restart
restart: stop start ## Restart the application

##@ Running Instance

.PHONY: shell
shell: ## Provides shell access to the running web container instance
	$(COMPOSE) exec web sh

.PHONY: logs
logs: ## Tails all container logs
	$(COMPOSE) logs -f

.PHONY: ps
ps: ## List all running containers
	$(COMPOSE) ps

.PHONY: open-web
open-web: ## Opens the website in the default browser
	open "http://0.0.0.0:8080"

# https://blog.thapaliya.com/posts/well-documented-makefiles/
.PHONY: help
help:
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
