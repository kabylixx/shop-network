DC   = docker compose
EXEC = $(DC) exec app
PHP  = $(EXEC) php bin/console

.DEFAULT_GOAL := help
.PHONY: help start start-and-seed up down build install migrate seed test test-unit test-functional create-test-db clear-cache clear-testcache api-demo sh

help: ## List the available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

start: up install migrate ## One-command startup: build + up + install + migrate
	@echo "✅ Stack ready: http://localhost:8080"

start-and-seed: start seed ## One-command startup with seeded demo data (start + seed)
	@echo "✅ Stack ready with demo data: http://localhost:8080"

up: ## Start the stack (build if needed)
	$(DC) up -d --build

down: ## Stop and remove the containers
	$(DC) down

build: ## (Re)build the application image
	$(DC) build

install: ## Install Composer dependencies inside the container
	$(EXEC) composer install --no-interaction

migrate: ## Run Doctrine migrations
	$(PHP) doctrine:migrations:migrate --no-interaction --allow-no-migration

seed: ## Seed the database with demo data (catalog, shops, stock)
	$(PHP) doctrine:fixtures:load --no-interaction

clear-cache: ## Clear the Symfony cache (dev)
	$(PHP) cache:clear

clear-testcache: ## Clear the Symfony cache (test)
	$(PHP) cache:clear --env=test

create-test-db: ## Create the test database schema
	$(PHP) doctrine:database:create --env=test --if-not-exists --no-interaction
	$(PHP) doctrine:migrations:migrate --env=test --no-interaction --allow-no-migration

test: create-test-db ## Run the PHPUnit test suite (prepares the test DB first)
	$(EXEC) vendor/bin/phpunit

test-unit: ## Run only the unit tests (no DB, fast)
	$(EXEC) vendor/bin/phpunit --group unit

test-functional: create-test-db ## Run only the functional tests (prepares the test DB first)
	$(EXEC) vendor/bin/phpunit --group functional

api-demo: ## Run a full API scenario (curl) against the running stack at :8080
	@bash bin/api-demo.sh

sh: ## Open a shell inside the application container
	$(EXEC) sh
