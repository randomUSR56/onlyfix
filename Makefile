.PHONY: help setup start stop restart down logs ps build install key migrate seed fresh storage cache-clear shell shell-node tinker test init clean prune hosts queue-listen queue-work queue-restart

PROJECT_NAME := onlyfix
COMPOSE_FILE := docker-compose.yml

# Platform detection
ifeq ($(OS),Windows_NT)
    PLATFORM := windows
    SHELL := cmd.exe
    .SHELLFLAGS := /c
    SETUP_SCRIPT := scripts\setup-windows.ps1
else
    UNAME_S := $(shell uname -s)
    ifeq ($(UNAME_S),Linux)
        PLATFORM := linux
    endif
    ifeq ($(UNAME_S),Darwin)
        PLATFORM := macos
    endif
    SETUP_SCRIPT := ./scripts/setup-unix.sh
endif

help: ## Show this help
	@echo OnlyFix Docker Management ($(PLATFORM))
	@echo ---
	@echo Usage: make [target]
	@echo ---
	@echo Targets:
	@echo   setup          Full setup (hosts + loopback + env)
	@echo   build          Build Docker images
	@echo   start          Start containers
	@echo   stop           Stop containers
	@echo   down           Stop and remove containers
	@echo   restart        Restart containers
	@echo   logs           Show container logs
	@echo   ps             List running containers
	@echo   install        Install Composer and NPM dependencies
	@echo   key            Generate Laravel app key
	@echo   migrate        Run database migrations
	@echo   seed           Seed database
	@echo   fresh          Fresh migration + seed
	@echo   storage        Create storage link
	@echo   cache-clear    Clear all caches
	@echo   queue-listen   Queue worker dev mode (live reload)
	@echo   queue-work     Queue worker production mode
	@echo   queue-restart  Restart queue worker
	@echo   shell          Open bash in app container
	@echo   shell-node     Open shell in node container
	@echo   tinker         Open Laravel Tinker
	@echo   test           Run tests
	@echo   init           Complete project initialization
	@echo   clean          Remove containers and volumes
	@echo   prune          Docker system prune

##@ Setup

setup: ## Full setup (hosts + loopback + env)
	@echo Running setup for $(PLATFORM)...
ifeq ($(PLATFORM),windows)
	@powershell -NoProfile -ExecutionPolicy Bypass -File $(SETUP_SCRIPT)
else
	@chmod +x $(SETUP_SCRIPT)
	@$(SETUP_SCRIPT)
endif

hosts: ## Update hosts file (requires admin/sudo)
	@echo Updating hosts file...
ifeq ($(PLATFORM),windows)
	@powershell -NoProfile -ExecutionPolicy Bypass -File $(SETUP_SCRIPT)
else
	@chmod +x $(SETUP_SCRIPT)
	@sudo $(SETUP_SCRIPT)
endif

##@ Docker

build: ## Build Docker images
	@echo Building Docker images...
	@docker-compose -f $(COMPOSE_FILE) build
	@echo Build complete

start: ## Start containers
	@echo Starting containers...
	@docker-compose -f $(COMPOSE_FILE) up -d
	@echo Containers started
	@echo ---
	@echo http://onlyfix.local
	@echo http://mailpit.onlyfix.local:8025
	@echo http://phpmyadmin.onlyfix.local:8080

stop: ## Stop containers
	@echo Stopping containers...
	@docker-compose -f $(COMPOSE_FILE) stop

down: ## Stop and remove containers
	@echo Removing containers...
	@docker-compose -f $(COMPOSE_FILE) down

restart: stop start ## Restart containers

logs: ## Show container logs (Ctrl+C to exit)
	@docker-compose -f $(COMPOSE_FILE) logs -f

ps: ## List running containers
	@docker-compose -f $(COMPOSE_FILE) ps

##@ Laravel

install: ## Install Composer and NPM dependencies
	@echo Installing dependencies...
	@docker-compose exec app composer install
	@docker-compose exec node npm install
	@echo Dependencies installed

key: ## Generate Laravel app key
	@docker-compose exec app php artisan key:generate

migrate: ## Run database migrations
	@echo Running migrations...
	@docker-compose exec app php artisan migrate

seed: ## Seed database
	@docker-compose exec app php artisan db:seed

fresh: ## Fresh migration + seed
	@echo Running fresh migration...
	@docker-compose exec app php artisan migrate:fresh --seed

storage: ## Create storage link
	@docker-compose exec app php artisan storage:link

cache-clear: ## Clear all caches
	@docker-compose exec app php artisan cache:clear
	@docker-compose exec app php artisan config:clear
	@docker-compose exec app php artisan route:clear
	@docker-compose exec app php artisan view:clear

##@ Queue

queue-listen: ## Start queue worker (dev mode - live reload)
	@docker-compose stop queue
	@docker-compose up -d queue
	@echo Queue worker started in listen mode (live reload)

queue-work: ## Start queue worker (production mode)
	@docker-compose stop queue
	@docker-compose run -d --rm --name onlyfix_queue_work app php artisan queue:work --verbose --tries=3 --timeout=90
	@echo Queue worker started in work mode (production)

queue-restart: ## Restart queue worker
	@docker-compose restart queue
	@echo Queue worker restarted

##@ Development

shell: ## Open bash in app container
	@docker-compose exec app bash

shell-node: ## Open shell in node container
	@docker-compose exec node sh

tinker: ## Open Laravel Tinker
	@docker-compose exec app php artisan tinker

test: ## Run tests
	@docker-compose exec app php artisan test

##@ Initialize

init: setup build start install key migrate storage ## Complete project initialization
	@echo ---
	@echo OnlyFix successfully initialized!
	@echo ---
	@echo Access:
	@echo   http://onlyfix.local
	@echo   http://mailpit.onlyfix.local:8025
	@echo   http://phpmyadmin.onlyfix.local:8080

##@ Cleanup

clean: ## Remove containers and volumes
	@echo Cleaning up...
	@docker-compose -f $(COMPOSE_FILE) down -v
	@echo Cleanup complete

prune: ## Docker system prune
	@docker system prune -af --volumes
