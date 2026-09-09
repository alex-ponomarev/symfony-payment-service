USER_ID=$(shell id -u)

DC = @USER_ID=$(USER_ID) docker compose
DC_RUN = ${DC} run --rm --no-deps payment
DC_EXEC = ${DC} exec payment
DC_DEV_EXEC = ${DC} exec -e APP_ENV=dev payment
DC_TEST_EXEC = ${DC} exec -e APP_ENV=test payment

.PHONY: help init build up stop start down reset restart console install migration migrate fixtures db-status test-init test success-message
.DEFAULT_GOAL := help

help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

init: ## Initialize environment.
	@$(MAKE) build
	@$(MAKE) install
	@$(MAKE) up
	@$(MAKE) migrate
	@$(MAKE) fixtures
	@$(MAKE) success-message

build: ## Build services.
	${DC} build $(c)

up: ## Create and start services.
	${DC} up -d --remove-orphans --wait $(c)

stop: ## Stop services.
	${DC} stop $(c)

start: ## Start services.
	${DC} start $(c)

down: ## Stop and remove containers without deleting volumes.
	${DC} down --remove-orphans

reset: ## Stop containers and delete volumes.
	${DC} down -v --remove-orphans

restart: stop start ## Restart services.

console: ## Login in console.
	${DC_EXEC} /bin/bash

install: ## Install dependencies without running the whole application.
	${DC_RUN} composer install

migration: ## Generate a database migration.
	${DC_DEV_EXEC} php bin/console make:migration

migrate: ## Apply database migrations.
	${DC_EXEC} php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

fixtures: ## Load missing development fixtures.
	${DC_DEV_EXEC} php bin/console doctrine:fixtures:load --append --no-interaction

db-status: ## Show database migration status.
	${DC_EXEC} php bin/console doctrine:migrations:status

test-init: ## Prepare schema and load fixtures in the test database.
	${DC_TEST_EXEC} php bin/console doctrine:database:create --if-not-exists
	${DC_TEST_EXEC} php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	${DC_TEST_EXEC} php bin/console doctrine:fixtures:load --no-interaction

test: test-init ## Run the test suite.
	${DC_TEST_EXEC} php bin/phpunit

success-message:
	@echo "You can now access the application at http://localhost:8337"
