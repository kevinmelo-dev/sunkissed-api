# Sunkissed Swimwear API — developer commands
# Usage: `make <target>`. Run `make help` to list targets.

# Run docker compose with current user's UID/GID so files aren't root-owned (WSL/Linux).
export UID := $(shell id -u)
export GID := $(shell id -g)

DC := docker compose
APP := $(DC) exec app

.DEFAULT_GOAL := help

## ----------------------------------------------------------------------------
## Environment
## ----------------------------------------------------------------------------

.PHONY: setup
setup: ## First-time setup: build, install deps, key, migrate
	$(DC) build
	$(DC) up -d
	$(APP) composer install
	$(APP) php artisan key:generate
	$(APP) php artisan migrate
	@echo "API ready at http://localhost:$${APP_PORT:-8080}"

.PHONY: up
up: ## Start all containers
	$(DC) up -d

.PHONY: down
down: ## Stop all containers
	$(DC) down

.PHONY: restart
restart: down up ## Restart all containers

.PHONY: build
build: ## Rebuild images
	$(DC) build

.PHONY: logs
logs: ## Tail container logs
	$(DC) logs -f

.PHONY: shell
shell: ## Open a bash shell in the app container
	$(APP) bash

## ----------------------------------------------------------------------------
## Application
## ----------------------------------------------------------------------------

.PHONY: install
install: ## Install composer dependencies
	$(APP) composer install

.PHONY: migrate
migrate: ## Run database migrations
	$(APP) php artisan migrate

.PHONY: fresh
fresh: ## Drop, re-migrate and seed the database
	$(APP) php artisan migrate:fresh --seed

.PHONY: seed
seed: ## Run database seeders
	$(APP) php artisan db:seed

.PHONY: tinker
tinker: ## Open Laravel Tinker
	$(APP) php artisan tinker

.PHONY: artisan
artisan: ## Run an artisan command, e.g. `make artisan cmd="route:list"`
	$(APP) php artisan $(cmd)

## ----------------------------------------------------------------------------
## Quality
## ----------------------------------------------------------------------------

.PHONY: test
test: ## Run the Pest test suite
	$(APP) ./vendor/bin/pest

.PHONY: test-coverage
test-coverage: ## Run tests with coverage
	$(APP) ./vendor/bin/pest --coverage

.PHONY: lint
lint: ## Fix code style with Laravel Pint
	$(APP) ./vendor/bin/pint

.PHONY: lint-test
lint-test: ## Check code style without modifying files
	$(APP) ./vendor/bin/pint --test

.PHONY: check
check: lint-test test ## Run style check and tests (CI gate)

## ----------------------------------------------------------------------------
## Help
## ----------------------------------------------------------------------------

.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'
