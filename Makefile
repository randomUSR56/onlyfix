.PHONY: help setup start stop restart down logs ps build install key migrate seed fresh storage cache-clear shell shell-node tinker test init clean prune hosts queue-listen queue-work queue-restart check-docker

PROJECT_NAME := onlyfix
COMPOSE_FILE := docker-compose.yml

# Docker compose command detection (docker-compose vs docker compose)
DOCKER_COMPOSE := $(shell command -v docker-compose 2>/dev/null)
ifeq ($(DOCKER_COMPOSE),)
    DOCKER_COMPOSE := $(shell command -v docker 2>/dev/null)
    ifneq ($(DOCKER_COMPOSE),)
        DOCKER_COMPOSE := docker compose
    endif
endif

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
	@echo "OnlyFix Docker Management ($(PLATFORM))"
	@echo "---"
	@echo "Usage: make [target]"
	@echo "---"
	@echo "Targets:"
	@echo "  setup          Full setup (hosts + loopback + env)"
	@echo "  check-docker   Verify Docker is installed and running"
	@echo "  build          Build Docker images"
	@echo "  start          Start containers"
	@echo "  stop           Stop containers"
	@echo "  down           Stop and remove containers"
	@echo "  restart        Restart containers"
	@echo "  logs           Show container logs"
	@echo "  ps             List running containers"
	@echo "  install        Install Composer and NPM dependencies"
	@echo "  key            Generate Laravel app key"
	@echo "  migrate        Run database migrations"
	@echo "  seed           Seed database"
	@echo "  fresh          Fresh migration + seed"
	@echo "  storage        Create storage link"
	@echo "  cache-clear    Clear all caches"
	@echo "  queue-listen   Queue worker dev mode (live reload)"
	@echo "  queue-work     Queue worker production mode"
	@echo "  queue-restart  Restart queue worker"
	@echo "  shell          Open bash in app container"
	@echo "  shell-node     Open shell in node container"
	@echo "  tinker         Open Laravel Tinker"
	@echo "  test           Run tests"
	@echo "  init           Complete project initialization"
	@echo "  clean          Remove containers and volumes"
	@echo "  prune          Docker system prune"

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

check-docker: ## Verify Docker is installed and running
	@command -v docker > /dev/null 2>&1 || (echo "\n❌ Docker is not installed!"; echo "\nInstall options:"; if command -v brew > /dev/null 2>&1; then echo "  brew install --cask docker"; fi; echo "  https://docs.docker.com/get-docker/"; echo ""; exit 1)
	@docker info > /dev/null 2>&1 || (echo "\n❌ Docker daemon is not running!"; echo "Please start Docker Desktop and try again."; echo ""; exit 1)
	@echo ✅ Docker is ready

build: check-docker ## Build Docker images
	@echo Building Docker images...
	@$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) build
	@echo Build complete

start: check-docker ## Start containers
	@echo Starting containers...
	@$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) up -d
	@echo Containers started
	@echo ---
	@echo http://onlyfix.local
	@echo http://mailpit.onlyfix.local:8025
	@echo http://phpmyadmin.onlyfix.local:8080

stop: ## Stop containers
	@echo Stopping containers...
	@$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) stop

down: ## Stop and remove containers
	@echo Removing containers...
	@$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) down

restart: stop start ## Restart containers

logs: ## Show container logs (Ctrl+C to exit)
	@$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) logs -f

ps: ## List running containers
	@$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) ps

##@ Laravel

install: ## Install Composer and NPM dependencies
	@echo Installing Composer dependencies...
	@$(DOCKER_COMPOSE) exec app composer install
	@echo Restarting node container to install NPM dependencies...
	@$(DOCKER_COMPOSE) restart node
	@echo Dependencies installed

key: ## Generate Laravel app key
	@$(DOCKER_COMPOSE) exec app php artisan key:generate

migrate: ## Run database migrations
	@echo Running migrations...
	@$(DOCKER_COMPOSE) exec app php artisan migrate

seed: ## Seed database
	@$(DOCKER_COMPOSE) exec app php artisan db:seed

fresh: ## Fresh migration + seed
	@echo Running fresh migration...
	@$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed

storage: ## Create storage link
	@$(DOCKER_COMPOSE) exec app php artisan storage:link

cache-clear: ## Clear all caches
	@$(DOCKER_COMPOSE) exec app php artisan cache:clear
	@$(DOCKER_COMPOSE) exec app php artisan config:clear
	@$(DOCKER_COMPOSE) exec app php artisan route:clear
	@$(DOCKER_COMPOSE) exec app php artisan view:clear

##@ Queue

queue-listen: ## Start queue worker (dev mode - live reload)
	@$(DOCKER_COMPOSE) stop queue
	@$(DOCKER_COMPOSE) up -d queue
	@echo Queue worker started in listen mode (live reload)

queue-work: ## Start queue worker (production mode)
	@$(DOCKER_COMPOSE) stop queue
	@$(DOCKER_COMPOSE) run -d --rm --name onlyfix_queue_work app php artisan queue:work --verbose --tries=3 --timeout=90
	@echo Queue worker started in work mode (production)

queue-restart: ## Restart queue worker
	@$(DOCKER_COMPOSE) restart queue
	@echo Queue worker restarted

##@ Development

shell: ## Open bash in app container
	@$(DOCKER_COMPOSE) exec app bash

shell-node: ## Open shell in node container
	@$(DOCKER_COMPOSE) exec node sh

tinker: ## Open Laravel Tinker
	@$(DOCKER_COMPOSE) exec app php artisan tinker

test: ## Run tests
	@$(DOCKER_COMPOSE) exec app php artisan test

##@ Initialize

init: ## Complete project initialization
ifeq ($(PLATFORM),windows)
	@powershell -NoProfile -ExecutionPolicy Bypass -File $(SETUP_SCRIPT)
else
	@chmod +x $(SETUP_SCRIPT)
	@if [ "$$(id -u)" -ne 0 ]; then echo "Setup requires sudo for hosts/loopback configuration..."; sudo $(SETUP_SCRIPT); else $(SETUP_SCRIPT); fi
endif
	@$(MAKE) build
	@$(MAKE) start
	@$(MAKE) install
	@$(MAKE) key
	@$(MAKE) migrate
	@$(MAKE) seed
	@$(MAKE) storage
	@echo "Generating Wayfinder routes..."
	@$(DOCKER_COMPOSE) exec app php artisan wayfinder:generate --with-form
	@echo ""
	@echo "============================================"
	@echo "  ✅ OnlyFix successfully initialized!"
	@echo "============================================"
	@echo ""
	@echo "🌐 Access:"
	@echo "   App:        http://onlyfix.local"
	@echo "   Mailpit:    http://mailpit.onlyfix.local:8025"
	@echo "   phpMyAdmin: http://phpmyadmin.onlyfix.local:8080"
	@echo ""
	@echo "🔑 Test Accounts:"
	@echo "   Admin:    admin@example.com / password"
	@echo "   Mechanic: mechanic@example.com / password"
	@echo "   User:     test@example.com / password"
	@echo ""

##@ Cleanup

clean: ## Remove containers and volumes
	@echo Cleaning up...
	@$(DOCKER_COMPOSE) -f $(COMPOSE_FILE) down -v
	@echo Cleanup complete

prune: ## Docker system prune
	@docker system prune -af --volumes
