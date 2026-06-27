DC   = docker compose
EXEC = $(DC) exec app
PHP  = $(EXEC) php bin/console

.DEFAULT_GOAL := help
.PHONY: help start up down build install migrate fixtures test sh

help: ## List the available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

start: up install migrate ## One-command startup: build + up + install + migrate
	@echo "✅ Stack ready: http://localhost:8080"

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

fixtures: ## Load demo fixtures
	$(PHP) doctrine:fixtures:load --no-interaction

test: ## Run the PHPUnit test suite
	$(EXEC) vendor/bin/phpunit

sh: ## Open a shell inside the application container
	$(EXEC) sh
