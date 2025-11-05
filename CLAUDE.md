# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 (PHP 8.3) enterprise API project with comprehensive infrastructure. The project uses a dockerized microservices architecture with extensive observability and development tools.

**Current Status**: Specification phase - DOMESTIKA.md contains the full infrastructure specification. The codebase needs to be implemented according to these specifications.

## Technology Stack

- **Framework**: Laravel 12 with PHP 8.3
- **Database**: PostgreSQL 16 with PostGIS extension
- **Cache/Session**: Redis 7
- **Message Broker**: RabbitMQ 3.12
- **Authentication**: JWT (tymon/jwt-auth) with refresh tokens
- **Authorization**: RBAC via spatie/laravel-permission
- **API Documentation**: OpenAPI via L5-Swagger (darkaonline/l5-swagger)
- **Queue**: RabbitMQ driver (vladimir-yuldashev/laravel-queue-rabbitmq)
- **Observability**: Prometheus + Grafana + Loki + Promtail
- **Web Server**: Nginx Alpine + PHP-FPM 8.3

## Essential Commands (via Makefile)

```bash
# Installation
make install          # Full installation
make install-fresh    # Fresh installation with clean state

# Container Management
make up              # Start all containers
make down            # Stop all containers
make restart         # Restart all containers
make logs            # View container logs
make shell           # Access app container shell

# Development
make composer        # Install PHP dependencies
make migrate         # Run database migrations
make migrate-fresh   # Fresh migrations with seeders
make seed            # Run database seeders
make test            # Run PHPUnit tests
make pint            # Run Laravel Pint (code style)
make swagger         # Generate API documentation

# Maintenance
make queue           # Start queue worker
make cache-clear     # Clear all caches
make optimize        # Optimize Laravel
make permissions     # Fix file permissions
make clean           # Clean environment completely
```

## Architecture

### API Structure

The API follows versioned REST principles:
- Base path: `/api/v1`
- Response format: JSON with RFC 7807 (Problem+JSON) for errors
- Date format: RFC 3339
- Authentication: JWT Bearer tokens (stateless)
- All responses include correlation-id for tracing

### Core Directory Structure

```
app/
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── Auth/          # Authentication endpoints
│   │   ├── Admin/         # Admin/RBAC endpoints
│   │   └── BaseController.php
│   ├── Middleware/        # Custom middleware
│   ├── Requests/          # Form request validation
│   └── Resources/         # API resources
├── Models/                # Eloquent models
├── Services/              # Business logic layer
├── Exceptions/            # Custom exceptions
└── Providers/             # Service providers

docker/
├── php/                   # PHP-FPM configuration
├── nginx/                 # Nginx configuration
├── prometheus/            # Metrics configuration
└── grafana/              # Dashboard provisioning
```

### Base Models

Essential infrastructure models (no business domain):
- **User**: Uses UUID, HasRoles, SoftDeletes traits
- **Role, Permission**: Spatie permission models
- **AuditLog**: System audit trail
- **ApiKey**: System-to-system authentication

### Essential API Endpoints

**Authentication** (`/api/v1/auth`):
- `POST /register` - User registration
- `POST /login` - User login (returns JWT + refresh token)
- `POST /logout` - Invalidate current token
- `POST /refresh` - Refresh access token
- `GET /me` - Get current user info

**System** (`/api/v1`):
- `GET /health` - Health check (DB, Redis, Queue status)
- `GET /metrics` - Prometheus metrics
- `GET /docs` - Swagger UI

**Admin** (`/api/v1/admin`):
- `GET /users` - User management
- `GET /roles` - Role management
- `GET /permissions` - Permission management

## Docker Services & Access

| Service | Port | URL | Credentials |
|---------|------|-----|-------------|
| API | 8000 | http://localhost:8000 | - |
| PostgreSQL | 5432 | localhost:5432 | See .env |
| pgAdmin | 5050 | http://localhost:5050 | admin@example.com / admin123 |
| Redis | 6379 | localhost:6379 | - |
| Redis Insight | 5540 | http://localhost:5540 | - |
| RabbitMQ Management | 15672 | http://localhost:15672 | See .env |
| Grafana | 3000 | http://localhost:3000 | admin / admin123 |
| Prometheus | 9090 | http://localhost:9090 | - |
| Mailhog | 8025 | http://localhost:8025 | - |
| MinIO Console | 9001 | http://localhost:9001 | minioadmin / minioadmin123 |
| Portainer | 9443 | https://localhost:9443 | - |

## Configuration

### Environment Variables

Critical environment variables (see `.env.example` for complete list):
- Database: PostgreSQL connection (host: `db`)
- Redis: Cache/session (host: `redis`)
- RabbitMQ: Queue connection (host: `rabbitmq`)
- JWT: Token secrets and TTL configuration
- CORS: Allowed origins
- Rate limiting: Per-minute limits
- Logging: JSON format with correlation-id

### Security Implementation

Required security features:
- JWT with token blacklist and refresh rotation
- Rate limiting per IP and per user
- CORS properly configured
- File upload validation
- Security headers middleware
- Comprehensive audit logging
- API key authentication for system integrations

## Testing

```bash
# Run all tests
make test

# Run specific test suite
php artisan test --filter=AuthenticationTest

# Run with coverage
php artisan test --coverage
```

Test structure:
- `tests/Feature/Auth/` - Authentication tests
- `tests/Feature/Admin/` - Admin/RBAC tests
- `tests/Unit/Services/` - Service layer tests
- `tests/Unit/Models/` - Model tests

## Development Workflow

1. **Initial Setup**: Run `make install` to build and configure everything
2. **Daily Development**: Use `make up` to start containers
3. **Code Changes**: Run `make pint` before committing to ensure code style
4. **Database Changes**: Run `make migrate` after creating migrations
5. **API Changes**: Run `make swagger` to update API documentation
6. **Testing**: Run `make test` to ensure all tests pass

## Observability

### Logging
- Format: JSON structured logs with correlation-id
- Storage: Loki (accessible via Grafana)
- Location: `storage/logs/laravel.log`

### Metrics
- Exposed at: `/api/v1/metrics` (Prometheus format)
- Dashboard: Grafana (pre-configured Laravel dashboards)
- Scrape interval: 30 seconds

### Monitoring
Access Grafana at http://localhost:3000 to view:
- Application performance metrics
- Database connection pools
- Queue processing statistics
- API response times and error rates

## Important Notes

- All IDs use UUID v4 (not auto-increment integers)
- All timestamps use RFC 3339 format
- All API errors follow RFC 7807 (Problem+JSON)
- All models should use proper traits (HasUuids, SoftDeletes where appropriate)
- Queue workers must be running for background jobs
- Redis required for sessions, cache, and queue tracking
- PostGIS extension available for future geolocation features

## Deployment

See `DEPLOYMENT.md` (to be created) for production deployment guidelines.

Key deployment considerations:
- Multi-stage Docker builds for production
- Environment-specific configurations
- Database migration strategy
- Queue worker supervision
- Log aggregation setup
- Backup procedures for PostgreSQL
