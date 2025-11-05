.PHONY: help install install-fresh up down restart logs shell composer migrate migrate-fresh seed test pint swagger clean cache-clear optimize permissions queue build setup-env key-generate jwt-secret status

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: build setup-env up composer key-generate jwt-secret migrate seed swagger permissions ## Full installation
	@echo "\033[32m✓ Installation complete!\033[0m"
	@echo "\033[33m→ Access the API at http://localhost:8000\033[0m"
	@echo "\033[33m→ Access Swagger docs at http://localhost:8000/api/documentation\033[0m"

install-fresh: clean build setup-env up composer key-generate jwt-secret migrate-fresh swagger permissions ## Fresh installation
	@echo "\033[32m✓ Fresh installation complete!\033[0m"
	@echo "\033[33m→ Access the API at http://localhost:8000\033[0m"

build: ## Build Docker containers
	docker compose build --no-cache

up: ## Start all containers
	docker compose up -d
	@echo "\033[32m✓ All containers started!\033[0m"

down: ## Stop all containers
	docker compose down
	@echo "\033[32m✓ All containers stopped!\033[0m"

restart: down up ## Restart all containers

logs: ## View logs from all containers
	docker compose logs -f

logs-app: ## View application logs
	docker compose logs -f app

logs-nginx: ## View nginx logs
	docker compose logs -f nginx

logs-queue: ## View queue worker logs
	docker compose logs -f queue

shell: ## Access app container shell
	docker compose exec app sh

shell-db: ## Access database shell
	docker compose exec db psql -U ${DB_USERNAME:-laravel} -d ${DB_DATABASE:-laravel_api}

setup-env: ## Setup environment file
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "\033[32m✓ Environment file created\033[0m"; \
	else \
		echo "\033[33m⚠ Environment file already exists\033[0m"; \
	fi

composer: ## Install composer dependencies
	docker compose exec app composer install --optimize-autoloader

composer-update: ## Update composer dependencies
	docker compose exec app composer update

key-generate: ## Generate application key
	docker compose exec app php artisan key:generate
	@echo "\033[32m✓ Application key generated\033[0m"

jwt-secret: ## Generate JWT secret
	docker compose exec app php artisan jwt:secret
	@echo "\033[32m✓ JWT secret generated\033[0m"

migrate: ## Run database migrations
	docker compose exec app php artisan migrate
	@echo "\033[32m✓ Migrations completed\033[0m"

migrate-fresh: ## Fresh migrations with seed
	docker compose exec app php artisan migrate:fresh --seed
	@echo "\033[32m✓ Fresh migrations with seed completed\033[0m"

migrate-rollback: ## Rollback last migration
	docker compose exec app php artisan migrate:rollback

seed: ## Run database seeders
	docker compose exec app php artisan db:seed
	@echo "\033[32m✓ Database seeded\033[0m"

seed-fresh: ## Fresh migrations and seed
	docker compose exec app php artisan migrate:fresh --seed
	@echo "\033[32m✓ Fresh migrations and database seeded\033[0m"

test: ## Run tests
	docker compose exec app php artisan test

test-coverage: ## Run tests with coverage
	docker compose exec app php artisan test --coverage

pint: ## Run Laravel Pint (code style)
	docker compose exec app ./vendor/bin/pint
	@echo "\033[32m✓ Code style fixed\033[0m"

pint-test: ## Test code style without fixing
	docker compose exec app ./vendor/bin/pint --test

swagger: ## Generate Swagger documentation
	docker compose exec app php artisan l5-swagger:generate
	@echo "\033[32m✓ Swagger documentation generated\033[0m"

queue: ## Start queue worker
	docker compose exec app php artisan queue:work

queue-restart: ## Restart queue workers
	docker compose exec app php artisan queue:restart
	@echo "\033[32m✓ Queue workers restarted\033[0m"

cache-clear: ## Clear all caches
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear
	@echo "\033[32m✓ All caches cleared\033[0m"

optimize: ## Optimize Laravel
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache
	@echo "\033[32m✓ Application optimized\033[0m"

permissions: ## Fix storage and cache permissions
	docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
	docker compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
	@echo "\033[32m✓ Permissions fixed\033[0m"

clean: down ## Clean environment completely
	docker compose down -v
	rm -rf vendor node_modules
	rm -f .env
	@echo "\033[32m✓ Environment cleaned\033[0m"

status: ## Show containers status
	docker compose ps

db-backup: ## Backup database
	docker compose exec db pg_dump -U ${DB_USERNAME:-laravel} ${DB_DATABASE:-laravel_api} > backup-$$(date +%Y%m%d-%H%M%S).sql
	@echo "\033[32m✓ Database backup created\033[0m"

db-restore: ## Restore database (usage: make db-restore FILE=backup.sql)
	docker compose exec -T db psql -U ${DB_USERNAME:-laravel} -d ${DB_DATABASE:-laravel_api} < $(FILE)
	@echo "\033[32m✓ Database restored\033[0m"

tinker: ## Open Laravel Tinker
	docker compose exec app php artisan tinker

routes: ## List all routes
	docker compose exec app php artisan route:list

publish-permissions: ## Publish Spatie permission config
	docker compose exec app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

publish-swagger: ## Publish L5-Swagger config
	docker compose exec app php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

publish-jwt: ## Publish JWT config
	docker compose exec app php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
