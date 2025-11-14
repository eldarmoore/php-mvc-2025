# Docker Setup Guide

Complete guide for running the PHP MVC Framework in Docker containers.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Docker Architecture](#docker-architecture)
- [Configuration](#configuration)
- [Development Workflow](#development-workflow)
- [Production Deployment](#production-deployment)
- [Troubleshooting](#troubleshooting)
- [Advanced Usage](#advanced-usage)

## Prerequisites

- Docker Engine 20.10+ ([Install Docker](https://docs.docker.com/get-docker/))
- Docker Compose 2.0+ ([Install Docker Compose](https://docs.docker.com/compose/install/))
- Git (for cloning the repository)
- At least 4GB of free RAM
- At least 10GB of free disk space

### Verify Installation

```bash
docker --version
docker-compose --version
```

## Quick Start

### Linux/Mac

```bash
# 1. Clone the repository (if not already)
git clone <repository-url>
cd Demolution

# 2. Run the setup script
make setup

# 3. Access the application
# Application: http://localhost:8080
# PHPMyAdmin: http://localhost:8081
# Mailhog: http://localhost:8025
```

### Windows

```batch
REM 1. Clone the repository (if not already)
git clone <repository-url>
cd Demolution

REM 2. Run the setup script
docker-setup.bat

REM 3. Access the application
REM Application: http://localhost:8080
REM PHPMyAdmin: http://localhost:8081
REM Mailhog: http://localhost:8025
```

## Docker Architecture

### Container Structure

```
┌─────────────────────────────────────────────────┐
│              Nginx (Web Server)                 │
│         Port 8080 (HTTP) / 8443 (HTTPS)        │
└────────────────┬────────────────────────────────┘
                 │
                 ↓ FastCGI
┌────────────────────────────────────────────────┐
│           PHP-FPM (Application)                │
│    - PHP 8.2                                   │
│    - Composer                                  │
│    - Xdebug (dev only)                         │
└─────────┬──────────────────────┬───────────────┘
          │                      │
          ↓                      ↓
┌─────────────────────┐  ┌──────────────────────┐
│   MySQL Database    │  │   Redis Cache        │
│   Port 3306         │  │   Port 6379          │
└─────────────────────┘  └──────────────────────┘

Development Only:
┌─────────────────────┐  ┌──────────────────────┐
│    PHPMyAdmin       │  │     Mailhog          │
│    Port 8081        │  │  Port 1025 / 8025    │
└─────────────────────┘  └──────────────────────┘
```

### Services

| Service     | Container Name       | Port(s)        | Description                    |
|-------------|---------------------|----------------|--------------------------------|
| app         | mvc_app             | 9000           | PHP-FPM application            |
| webserver   | mvc_webserver       | 8080, 8443     | Nginx web server               |
| db          | mvc_db              | 3306           | MySQL 8.0 database             |
| redis       | mvc_redis           | 6379           | Redis cache                    |
| phpmyadmin  | mvc_phpmyadmin      | 8081           | Database admin (dev only)      |
| mailhog     | mvc_mailhog         | 1025, 8025     | Email testing (dev only)       |

### Docker Volumes

| Volume      | Purpose                           | Persistent |
|-------------|-----------------------------------|------------|
| mysql-data  | MySQL database files              | Yes        |
| redis-data  | Redis persistence                 | Yes        |
| ./          | Application source code (mounted) | No         |

## Configuration

### Environment Variables

Create a `.env` file in the project root (copy from `.env.docker.example`):

```bash
cp .env.docker.example .env
```

#### Key Variables

```env
# Application
APP_ENV=development          # development, production, testing
APP_DEBUG=true               # Enable/disable debug mode
APP_PORT=8080                # HTTP port
APP_SSL_PORT=8443            # HTTPS port

# Database (Docker service names)
DB_HOST=db                   # Use service name, not localhost
DB_DATABASE=mvc_framework
DB_USERNAME=mvc_user
DB_PASSWORD=secret

# Redis (Docker service name)
REDIS_HOST=redis             # Use service name, not localhost
REDIS_PORT=6379

# Xdebug
XDEBUG_MODE=off              # off, debug, develop, coverage
```

### PHP Configuration

Custom PHP settings are in `docker/php/conf/`:

- `php.ini` - Development settings
- `php-prod.ini` - Production settings
- `opcache.ini` - OPcache for production
- `xdebug.ini` - Xdebug configuration

### Nginx Configuration

Nginx configs are in `docker/nginx/`:

- `nginx.conf` - Main Nginx configuration
- `conf.d/default.conf` - Development virtual host
- `conf.d/prod.conf` - Production virtual host with SSL

## Development Workflow

### Starting the Environment

```bash
# Start all services
make up

# Or with Windows
docker-start.bat

# Start with development tools (PHPMyAdmin, Mailhog)
make up-dev
```

### Viewing Logs

```bash
# All services
make logs

# Specific service
make logs-app      # PHP-FPM
make logs-nginx    # Nginx
make logs-db       # MySQL

# Or use docker-compose directly
docker-compose logs -f app
```

### Executing Commands

```bash
# Open shell in app container
make shell

# Run Composer commands
make composer                    # composer install
make composer-update             # composer update
docker-compose exec app composer require vendor/package

# Database operations
make db-shell                    # Open MySQL CLI
make db-shell-root              # MySQL CLI as root

# Redis operations
make redis-shell                # Open Redis CLI
```

### Debugging with Xdebug

#### Enable Xdebug

```bash
# Linux/Mac
make xdebug-on

# Windows - Edit .env file
# Change: XDEBUG_MODE=off
# To: XDEBUG_MODE=debug,develop,coverage
# Then restart: docker-compose restart app
```

#### IDE Configuration

**PHPStorm:**

1. Go to Settings → PHP → Servers
2. Add new server:
   - Name: `Docker`
   - Host: `localhost`
   - Port: `8080`
   - Debugger: `Xdebug`
   - Path mappings: Project root → `/var/www/html`

**VS Code:**

Create `.vscode/launch.json`:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

#### Disable Xdebug

```bash
# Linux/Mac
make xdebug-off

# Windows - Edit .env and restart
```

### Database Management

#### Access PHPMyAdmin

1. Start containers with development profile:
   ```bash
   docker-compose --profile development up -d
   ```

2. Open browser: http://localhost:8081

3. Login with credentials from `.env`:
   - Server: `db`
   - Username: `mvc_user`
   - Password: `secret`

#### Backup Database

```bash
# Create backup
make backup

# Backup saved to: backups/backup_YYYYMMDD_HHMMSS.sql
```

#### Restore Database

```bash
# Interactive restore
make restore

# Manual restore
docker-compose exec -T db mysql -u root -proot mvc_framework < backups/your_backup.sql
```

#### Run Migrations

```bash
# Seed initial data
make seed

# Or manually
docker-compose exec -T db mysql -u root -proot mvc_framework < database/migrations/001_create_users_table.sql
```

### Email Testing with Mailhog

Mailhog captures all outgoing emails in development:

1. Configure application to use Mailhog (already set in `.env.docker.example`):
   ```env
   MAIL_HOST=mailhog
   MAIL_PORT=1025
   ```

2. Start containers with development profile:
   ```bash
   docker-compose --profile development up -d
   ```

3. View captured emails: http://localhost:8025

### File Permissions

If you encounter permission issues:

```bash
# Fix permissions
make permissions

# Or manually
docker-compose exec --user root app chown -R appuser:appuser /var/www/html
docker-compose exec --user root app chmod -R 755 /var/www/html/storage
```

## Production Deployment

### Differences from Development

- **No Xdebug** - Removed for performance
- **OPcache enabled** - PHP bytecode caching
- **Optimized images** - Smaller image size with multi-stage builds
- **Security hardening** - Non-root user, minimal packages
- **SSL/TLS** - HTTPS enabled with certificates
- **Resource limits** - CPU and memory constraints
- **Health checks** - Container health monitoring

### Building for Production

```bash
# Build production images
make prod-build

# Or manually
docker-compose -f docker-compose.prod.yml build
```

### Configuration

1. **Create production `.env` file:**
   ```bash
   cp .env.docker.example .env.production
   ```

2. **Update values:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   # Strong passwords!
   DB_PASSWORD=your_strong_password
   DB_ROOT_PASSWORD=your_strong_root_password
   ```

3. **SSL Certificates:**

   Place your SSL certificate files in `docker/ssl/`:
   ```
   docker/ssl/cert.pem
   docker/ssl/key.pem
   ```

   Or generate self-signed for testing:
   ```bash
   mkdir -p docker/ssl
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout docker/ssl/key.pem \
     -out docker/ssl/cert.pem
   ```

### Deploying

```bash
# Start production containers
make prod-up

# Or manually
docker-compose -f docker-compose.prod.yml up -d

# Check container status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f
```

### Monitoring

```bash
# Container stats
docker stats

# Health checks
docker-compose ps

# Application logs
docker-compose -f docker-compose.prod.yml logs -f app

# Nginx logs
docker-compose -f docker-compose.prod.yml logs -f webserver
```

### Updating

```bash
# Pull latest code
git pull

# Rebuild images
docker-compose -f docker-compose.prod.yml build

# Restart with zero downtime (if using load balancer)
docker-compose -f docker-compose.prod.yml up -d --no-deps --build app

# Or full restart
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d
```

## Troubleshooting

### Common Issues

#### Port Already in Use

**Problem:** `Error: Port 8080 is already allocated`

**Solution:**
```bash
# Check what's using the port
# Linux/Mac:
lsof -i :8080

# Windows:
netstat -ano | findstr :8080

# Change port in .env
APP_PORT=8081

# Restart containers
docker-compose down && docker-compose up -d
```

#### Permission Denied Errors

**Problem:** `Permission denied` when accessing files

**Solution:**
```bash
# Fix permissions
make permissions

# Check user ID in container
docker-compose exec app id

# Update USER_ID and GROUP_ID in .env to match your host user
# Linux/Mac:
echo "USER_ID=$(id -u)" >> .env
echo "GROUP_ID=$(id -g)" >> .env

# Rebuild
docker-compose down
docker-compose build
docker-compose up -d
```

#### Database Connection Failed

**Problem:** `SQLSTATE[HY000] [2002] Connection refused`

**Solution:**
```bash
# Verify DB service is running
docker-compose ps db

# Check if DB is ready
docker-compose logs db

# Wait for DB to be ready (takes 10-20 seconds on first start)
docker-compose exec db mysqladmin ping -h localhost -u root -proot

# Verify connection settings in .env
DB_HOST=db  # Must be 'db', not 'localhost' or '127.0.0.1'
```

#### Containers Keep Restarting

**Problem:** Container restarts immediately after starting

**Solution:**
```bash
# Check logs for errors
docker-compose logs app

# Common causes:
# 1. Syntax errors in PHP
# 2. Missing dependencies
# 3. Port conflicts
# 4. File permission issues

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

#### Composer Install Fails

**Problem:** Memory exhausted during `composer install`

**Solution:**
```bash
# Increase PHP memory limit temporarily
docker-compose exec app php -d memory_limit=-1 /usr/bin/composer install

# Or update php.ini
# Edit: docker/php/conf/php.ini
# Change: memory_limit = 512M (or higher)
# Restart: docker-compose restart app
```

### Debugging Tips

#### Check Container Health

```bash
# All containers
docker-compose ps

# Specific service
docker inspect mvc_app --format='{{.State.Health.Status}}'
```

#### Access Container Logs

```bash
# Real-time logs
docker-compose logs -f

# Last 100 lines
docker-compose logs --tail=100 app

# Since specific time
docker-compose logs --since 2024-01-01T12:00:00 app
```

#### Execute Commands Inside Containers

```bash
# As default user
docker-compose exec app bash
docker-compose exec db mysql -u root -p

# As root
docker-compose exec --user root app bash

# One-off command
docker-compose exec app php -v
docker-compose exec db mysqladmin version
```

#### Inspect Networks

```bash
# List networks
docker network ls

# Inspect app network
docker network inspect demolution_app-network

# Test connectivity between containers
docker-compose exec app ping db
docker-compose exec app ping redis
```

#### Reset Everything

```bash
# Nuclear option - removes all containers, volumes, and images
docker-compose down -v --rmi all
docker system prune -a --volumes

# Then rebuild
make setup
```

## Advanced Usage

### Custom PHP Extensions

Add to `docker/php/Dockerfile`:

```dockerfile
# Install additional PHP extensions
RUN docker-php-ext-install \
    intl \
    soap \
    sockets
```

Rebuild:
```bash
docker-compose build app
docker-compose up -d
```

### Multiple Environments

Create environment-specific compose files:

```bash
# Staging
docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d

# Production
docker-compose -f docker-compose.prod.yml up -d
```

### CI/CD Integration

Example GitHub Actions workflow (`.github/workflows/docker.yml`):

```yaml
name: Docker Build

on: [push]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Build Docker images
        run: docker-compose build

      - name: Run tests
        run: docker-compose run app ./vendor/bin/phpunit

      - name: Push to registry (production only)
        if: github.ref == 'refs/heads/main'
        run: |
          docker tag mvc_app:latest registry.example.com/mvc_app:latest
          docker push registry.example.com/mvc_app:latest
```

### Database Replication

For high availability, set up MySQL replication (beyond scope of this guide):
- Master-Slave configuration
- Multiple read replicas
- Automatic failover

### Horizontal Scaling

Scale specific services:

```bash
# Scale PHP-FPM containers
docker-compose up -d --scale app=3

# Use load balancer (Nginx, HAProxy, Traefik)
# Configure load balancer to distribute requests
```

### Container Resource Limits

Edit `docker-compose.yml`:

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M
```

## Security Best Practices

1. **Never expose database ports in production**
   ```yaml
   # Comment out or remove:
   # ports:
   #   - "3306:3306"
   ```

2. **Use strong passwords**
   - Generate with: `openssl rand -base64 32`

3. **Keep images updated**
   ```bash
   docker-compose pull
   docker-compose up -d
   ```

4. **Scan for vulnerabilities**
   ```bash
   docker scan mvc_app:latest
   ```

5. **Use secrets management**
   - Docker Secrets
   - Environment variable injection from CI/CD
   - External secrets managers (Vault, AWS Secrets Manager)

6. **Regular backups**
   - Automate database backups
   - Store backups off-site
   - Test restoration regularly

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PHP Docker Official Images](https://hub.docker.com/_/php)
- [Nginx Docker Official Images](https://hub.docker.com/_/nginx)
- [MySQL Docker Official Images](https://hub.docker.com/_/mysql)

## Support

For issues and questions:
- Check the [Troubleshooting](#troubleshooting) section
- Review container logs
- Open an issue on the repository

---

**Last Updated:** 2024
**Docker Version:** 20.10+
**Docker Compose Version:** 2.0+
