.PHONY: help build up down restart logs shell composer test clean backup

# Default target
help:
	@echo "Available commands:"
	@echo "  make setup         - Initial setup (copy .env, build, start)"
	@echo "  make build         - Build Docker images"
	@echo "  make up            - Start all containers"
	@echo "  make down          - Stop all containers"
	@echo "  make restart       - Restart all containers"
	@echo "  make logs          - View logs from all containers"
	@echo "  make shell         - Open shell in app container"
	@echo "  make composer      - Run composer install"
	@echo "  make test          - Run PHPUnit tests"
	@echo "  make clean         - Remove all containers and volumes"
	@echo "  make backup        - Backup database"
	@echo "  make restore       - Restore database from backup"
	@echo "  make prod-build    - Build production images"
	@echo "  make prod-up       - Start production containers"
	@echo "  make xdebug-on     - Enable Xdebug"
	@echo "  make xdebug-off    - Disable Xdebug"
	@echo "  make db-shell      - Open MySQL shell"
	@echo "  make redis-shell   - Open Redis CLI"
	@echo "  make permissions   - Fix file permissions"

# Initial setup
setup:
	@echo "Setting up project..."
	@if [ ! -f .env ]; then cp .env.docker.example .env; fi
	@make build
	@make up
	@make composer
	@echo "Setup complete! Access the application at http://localhost:8080"

# Build Docker images
build:
	docker-compose build --no-cache

# Start containers
up:
	docker-compose up -d
	@echo "Containers started. Application: http://localhost:8080, PHPMyAdmin: http://localhost:8081"

# Start containers with development profile
up-dev:
	docker-compose --profile development up -d

# Stop containers
down:
	docker-compose down

# Restart containers
restart:
	@make down
	@make up

# View logs
logs:
	docker-compose logs -f

# View logs for specific service
logs-app:
	docker-compose logs -f app

logs-nginx:
	docker-compose logs -f webserver

logs-db:
	docker-compose logs -f db

# Open shell in app container
shell:
	docker-compose exec app /bin/bash

# Open shell as root in app container
shell-root:
	docker-compose exec --user root app /bin/bash

# Run composer install
composer:
	docker-compose exec app composer install

# Run composer update
composer-update:
	docker-compose exec app composer update

# Run composer dump-autoload
composer-dump:
	docker-compose exec app composer dump-autoload

# Run PHPUnit tests
test:
	docker-compose exec app ./vendor/bin/phpunit

# Clean up (remove containers and volumes)
clean:
	docker-compose down -v --remove-orphans
	docker system prune -f

# Backup database
backup:
	@mkdir -p backups
	docker-compose exec db mysqldump -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "Database backed up to backups/ directory"

# Restore database from backup
restore:
	@echo "Available backups:"
	@ls -1 backups/*.sql
	@read -p "Enter backup filename: " backup; \
	docker-compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} < backups/$$backup
	@echo "Database restored"

# Production build
prod-build:
	docker-compose -f docker-compose.prod.yml build --no-cache

# Production up
prod-up:
	docker-compose -f docker-compose.prod.yml up -d

# Production down
prod-down:
	docker-compose -f docker-compose.prod.yml down

# Enable Xdebug
xdebug-on:
	@echo "Enabling Xdebug..."
	@sed -i.bak 's/XDEBUG_MODE=off/XDEBUG_MODE=debug,develop,coverage/' .env
	@make restart
	@echo "Xdebug enabled"

# Disable Xdebug
xdebug-off:
	@echo "Disabling Xdebug..."
	@sed -i.bak 's/XDEBUG_MODE=debug,develop,coverage/XDEBUG_MODE=off/' .env
	@make restart
	@echo "Xdebug disabled"

# Open MySQL shell
db-shell:
	docker-compose exec db mysql -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE}

# Open MySQL shell as root
db-shell-root:
	docker-compose exec db mysql -u root -p${DB_ROOT_PASSWORD}

# Open Redis CLI
redis-shell:
	docker-compose exec redis redis-cli

# Fix file permissions
permissions:
	docker-compose exec --user root app chown -R appuser:appuser /var/www/html
	docker-compose exec --user root app chmod -R 755 /var/www/html/storage
	@echo "Permissions fixed"

# Clear application cache
cache-clear:
	docker-compose exec app rm -rf storage/cache/*
	@echo "Cache cleared"

# View container stats
stats:
	docker stats

# Check container health
health:
	@echo "Container Health Status:"
	@docker-compose ps

# Rebuild and restart specific service
rebuild-app:
	docker-compose up -d --no-deps --build app

rebuild-nginx:
	docker-compose up -d --no-deps --build webserver

# Install a new composer package
composer-require:
	@read -p "Enter package name: " package; \
	docker-compose exec app composer require $$package

# Remove a composer package
composer-remove:
	@read -p "Enter package name: " package; \
	docker-compose exec app composer remove $$package

# Database migrations (if you add migration system)
migrate:
	docker-compose exec app php migrations.php up

# Seed database
seed:
	docker-compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} < database/migrations/001_create_users_table.sql
	@echo "Database seeded"
