# Docker Architecture Overview

## Container Stack

```
┌──────────────────────────────────────────────────────────────┐
│                      Host Machine                             │
│                                                               │
│  ┌────────────────────────────────────────────────────────┐  │
│  │              Docker Network (app-network)              │  │
│  │                                                        │  │
│  │  ┌─────────────────────────────────────────────────┐  │  │
│  │  │         Nginx Web Server (Port 8080/8443)       │  │  │
│  │  │  • Serves static files                          │  │  │
│  │  │  • Proxy to PHP-FPM                             │  │  │
│  │  │  • SSL/TLS termination                          │  │  │
│  │  │  • Gzip compression                             │  │  │
│  │  └──────────────────┬──────────────────────────────┘  │  │
│  │                     │ FastCGI                          │  │
│  │                     ↓                                  │  │
│  │  ┌─────────────────────────────────────────────────┐  │  │
│  │  │      PHP-FPM Application (Port 9000)            │  │  │
│  │  │  • PHP 8.2-FPM                                  │  │  │
│  │  │  • Composer                                     │  │  │
│  │  │  • Xdebug (dev only)                            │  │  │
│  │  │  • All PHP extensions                           │  │  │
│  │  └────────┬───────────────────────┬────────────────┘  │  │
│  │           │                       │                    │  │
│  │           ↓                       ↓                    │  │
│  │  ┌──────────────────┐   ┌──────────────────────────┐  │  │
│  │  │  MySQL Database  │   │    Redis Cache           │  │  │
│  │  │  (Port 3306)     │   │    (Port 6379)           │  │  │
│  │  │  • MySQL 8.0     │   │    • In-memory store     │  │  │
│  │  │  • Persistent    │   │    • Session storage     │  │  │
│  │  │    data volume   │   │    • Cache storage       │  │  │
│  │  └──────────────────┘   │    • Persistent volume   │  │  │
│  │                         └──────────────────────────┘  │  │
│  │                                                        │  │
│  │  Development Only:                                    │  │
│  │  ┌──────────────────┐   ┌──────────────────────────┐  │  │
│  │  │   PHPMyAdmin     │   │      Mailhog             │  │  │
│  │  │   (Port 8081)    │   │  SMTP: 1025 Web: 8025   │  │  │
│  │  │  • DB admin      │   │  • Email testing         │  │  │
│  │  └──────────────────┘   └──────────────────────────┘  │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                               │
│  Volumes:                                                     │
│  • mysql-data    → MySQL database files                      │
│  • redis-data    → Redis persistence                         │
│  • ./            → Application source (bind mount)           │
│  • storage/logs  → Application logs                          │
└───────────────────────────────────────────────────────────────┘
```

## File Structure

```
Demolution/
├── docker/                          # Docker configuration files
│   ├── php/
│   │   ├── Dockerfile              # Development PHP-FPM
│   │   ├── Dockerfile.prod         # Production PHP-FPM
│   │   └── conf/
│   │       ├── php.ini             # Development PHP config
│   │       ├── php-prod.ini        # Production PHP config
│   │       ├── opcache.ini         # OPcache config
│   │       └── xdebug.ini          # Xdebug config
│   ├── nginx/
│   │   ├── nginx.conf              # Main Nginx config
│   │   └── conf.d/
│   │       ├── default.conf        # Development vhost
│   │       └── prod.conf           # Production vhost (with SSL)
│   ├── mysql/
│   │   └── my.cnf                  # MySQL configuration
│   └── redis/
│       └── redis.conf              # Redis configuration
├── docker-compose.yml              # Development orchestration
├── docker-compose.prod.yml         # Production orchestration
├── .dockerignore                   # Files to exclude from build
├── .env.docker.example             # Docker environment template
├── Makefile                        # Helper commands (Linux/Mac)
├── docker-setup.bat                # Setup script (Windows)
├── docker-start.bat                # Start script (Windows)
├── docker-stop.bat                 # Stop script (Windows)
└── DOCKER.md                       # Docker documentation
```

## Data Flow

### HTTP Request Flow

```
1. Client Browser
   ↓ HTTP/HTTPS (Port 8080/8443)
2. Nginx Container
   • Routes request
   • Serves static files directly
   • Proxies PHP requests
   ↓ FastCGI (Port 9000)
3. PHP-FPM Container
   • Executes PHP code
   • Processes request through MVC
   • Queries database/cache
   ↓ MySQL Protocol (Port 3306)  ↓ Redis Protocol (Port 6379)
4. MySQL Container              5. Redis Container
   • Returns data                  • Returns cached data
   ↑                               ↑
6. Response flows back through containers
   ↓
7. Client receives response
```

### Development Workflow

```
Developer
    ↓ Edit code
Local Filesystem (bind mount)
    ↓ Auto-sync
PHP-FPM Container
    ↓ Execute
View in Browser
    ↓ Inspect
PHPMyAdmin / Logs
```

## Network Communication

### Container DNS Resolution

Containers communicate using service names defined in docker-compose.yml:

- `app` → PHP-FPM container
- `webserver` → Nginx container
- `db` → MySQL container
- `redis` → Redis container
- `mailhog` → Mailhog container

**Example:** Application connects to database using `DB_HOST=db`

### Port Mappings

| Service    | Internal Port | External Port | Protocol |
|------------|--------------|---------------|----------|
| Nginx      | 80           | 8080          | HTTP     |
| Nginx      | 443          | 8443          | HTTPS    |
| PHP-FPM    | 9000         | -             | FastCGI  |
| MySQL      | 3306         | 3306          | MySQL    |
| Redis      | 6379         | 6379          | Redis    |
| PHPMyAdmin | 80           | 8081          | HTTP     |
| Mailhog    | 1025         | 1025          | SMTP     |
| Mailhog    | 8025         | 8025          | HTTP     |

## Volume Persistence

### Named Volumes (Persistent)

```
mysql-data:
  Location: /var/lib/docker/volumes/
  Contains: MySQL database files
  Survives: Container recreation
  Backup: Required

redis-data:
  Location: /var/lib/docker/volumes/
  Contains: Redis persistence files
  Survives: Container recreation
  Backup: Optional (cache data)
```

### Bind Mounts (Development)

```
./                    → /var/www/html
  Live code updates, hot reload

./storage/logs        → /var/www/html/storage/logs
  Application logs

./docker/php/conf     → /usr/local/etc/php/conf.d
  PHP configuration
```

## Security Layers

### Container Security

1. **Non-root User**
   - PHP-FPM runs as `appuser` (UID 1000)
   - No privileged operations

2. **Network Isolation**
   - Containers only communicate via internal network
   - Database not exposed externally in production

3. **Read-only Mounts**
   - Production uses read-only file mounts where possible

4. **Resource Limits**
   - CPU and memory limits prevent resource exhaustion

### Application Security

1. **Nginx Layer**
   - Request filtering
   - Rate limiting
   - Security headers
   - SSL/TLS termination

2. **PHP Layer**
   - Disabled dangerous functions (production)
   - OPcache (production)
   - Xdebug only in development

3. **Database Layer**
   - Separate user credentials
   - Connection from PHP container only

## Performance Optimization

### Development

- **Xdebug enabled** for debugging
- **No OPcache** for instant code updates
- **Bind mounts** for live code editing
- **Extended timeouts** for debugging

### Production

- **OPcache enabled** with JIT compilation
- **No Xdebug** (performance impact)
- **Optimized Composer** autoloader
- **Multi-stage builds** for smaller images
- **Gzip compression** in Nginx
- **Static file caching** with long expiry
- **Resource limits** prevent overuse

## Scaling Strategy

### Horizontal Scaling

```
Load Balancer
    ├── Nginx + PHP-FPM (Instance 1)
    ├── Nginx + PHP-FPM (Instance 2)
    └── Nginx + PHP-FPM (Instance 3)
         ↓
    MySQL (Primary)
         ├── Read Replica 1
         └── Read Replica 2
         ↓
    Redis Cluster
```

Scale with:
```bash
docker-compose up -d --scale app=3
```

### Vertical Scaling

Adjust resources in docker-compose.yml:
```yaml
deploy:
  resources:
    limits:
      cpus: '2'
      memory: 1G
```

## Monitoring

### Health Checks

All containers have health checks:
- **PHP-FPM**: `php -v`
- **Nginx**: `wget --spider http://localhost/health`
- **MySQL**: `mysqladmin ping`
- **Redis**: `redis-cli ping`

### Logging

Logs accessible via:
```bash
# All containers
docker-compose logs -f

# Specific service
docker-compose logs -f app

# Centralized logging
# Configure log drivers in docker-compose.yml
```

### Metrics

```bash
# Resource usage
docker stats

# Container status
docker-compose ps

# Health status
docker inspect --format='{{.State.Health.Status}}' mvc_app
```

## Backup Strategy

### Database Backup

```bash
# Automated backup
make backup

# Manual backup
docker-compose exec db mysqldump \
  -u root -proot mvc_framework > backup.sql

# Backup with compression
docker-compose exec db mysqldump \
  -u root -proot mvc_framework | gzip > backup.sql.gz
```

### Volume Backup

```bash
# Backup volumes
docker run --rm \
  -v demolution_mysql-data:/data \
  -v $(pwd)/backups:/backup \
  alpine tar czf /backup/mysql-data.tar.gz -C /data .
```

## Disaster Recovery

### Database Restore

```bash
# From backup
docker-compose exec -T db mysql \
  -u root -proot mvc_framework < backup.sql

# From compressed backup
gunzip < backup.sql.gz | docker-compose exec -T db mysql \
  -u root -proot mvc_framework
```

### Complete Environment Restore

```bash
# 1. Clone repository
git clone <repo>

# 2. Restore .env file
cp backup/.env .env

# 3. Build containers
docker-compose build

# 4. Restore volumes
docker run --rm \
  -v demolution_mysql-data:/data \
  -v $(pwd)/backups:/backup \
  alpine tar xzf /backup/mysql-data.tar.gz -C /data

# 5. Start containers
docker-compose up -d
```

## CI/CD Integration

### Build Pipeline

```
1. Git Push
   ↓
2. CI System (GitHub Actions, GitLab CI, Jenkins)
   • Checkout code
   • Build Docker images
   • Run tests in containers
   • Push images to registry
   ↓
3. Registry (Docker Hub, ECR, GCR)
   • Store versioned images
   ↓
4. Deployment System
   • Pull images
   • Update containers
   • Run health checks
   ↓
5. Production
   • Zero-downtime deployment
   • Automatic rollback on failure
```

## Best Practices

✅ **DO**
- Use named volumes for data persistence
- Implement health checks
- Use multi-stage builds for production
- Run containers as non-root user
- Keep images updated
- Use .dockerignore to reduce build context
- Tag images with versions
- Monitor container resources
- Regular backups
- Test disaster recovery

❌ **DON'T**
- Expose database ports in production
- Run as root user
- Store secrets in images
- Use `:latest` tag in production
- Ignore security updates
- Skip health checks
- Mix development and production configs
- Hardcode environment-specific values

---

This architecture provides a scalable, secure, and maintainable foundation for deploying your PHP MVC framework.
