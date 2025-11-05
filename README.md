# Domestika Laravel API

Enterprise-grade Laravel 12 REST API with complete infrastructure, JWT authentication, RBAC, and comprehensive observability.

## 🚀 Features

- **Laravel 12** with PHP 8.3
- **JWT Authentication** with refresh tokens (tymon/jwt-auth)
- **RBAC** (Role-Based Access Control) via Spatie Permissions
- **API Documentation** with OpenAPI/Swagger
- **PostgreSQL 16** with PostGIS support
- **Redis 7** for cache and sessions
- **RabbitMQ 3.12** for message queuing
- **Observability Stack**: Prometheus + Grafana + Loki + Promtail
- **Docker Compose** with 13+ services
- **Development Tools**: pgAdmin, Redis Insight, Mailhog, MinIO, Portainer
- **Comprehensive Testing** with PHPUnit
- **Code Quality** with Laravel Pint

## 📋 Prerequisites

- Docker and Docker Compose
- Make (optional, but recommended)
- Git

## 🛠️ Installation

### Quick Start

```bash
# Clone the repository
git clone <your-repo-url>
cd domestika

# Install and start the project
make install
```

This will:
1. Build all Docker containers
2. Copy environment file
3. Install Composer dependencies
4. Generate application and JWT keys
5. Run database migrations
6. Seed the database with default users and roles
7. Generate Swagger documentation

### Manual Installation

If you prefer manual setup:

```bash
# Build containers
docker compose build

# Copy environment file
cp .env.example .env

# Start containers
docker compose up -d

# Install dependencies
docker compose exec app composer install

# Generate keys
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret

# Run migrations and seeders
docker compose exec app php artisan migrate --seed

# Generate Swagger docs
docker compose exec app php artisan l5-swagger:generate
```

## 🎯 Usage

### Available Make Commands

```bash
make help              # Show all available commands
make install           # Full installation
make install-fresh     # Fresh installation (cleans everything)
make up                # Start containers
make down              # Stop containers
make restart           # Restart containers
make logs              # View logs
make shell             # Access app container
make test              # Run tests
make pint              # Fix code style
make swagger           # Generate API documentation
```

### Default Users

After seeding, you can login with these users:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@domestika.local | admin123 |
| Moderator | moderator@domestika.local | moderator123 |
| User | user@domestika.local | user123 |

## 🌐 Access Points

| Service | URL | Credentials |
|---------|-----|-------------|
| API | http://localhost:8000 | - |
| Swagger UI | http://localhost:8000/api/documentation | - |
| Grafana | http://localhost:3000 | admin / admin123 |
| Prometheus | http://localhost:9090 | - |
| pgAdmin | http://localhost:5050 | admin@example.com / admin123 |
| Redis Insight | http://localhost:5540 | - |
| RabbitMQ Management | http://localhost:15672 | laravel / secret |
| Mailhog | http://localhost:8025 | - |
| MinIO Console | http://localhost:9001 | minioadmin / minioadmin123 |
| Portainer | https://localhost:9443 | - |

## 📚 API Documentation

### Authentication Endpoints

```bash
# Register
POST /api/v1/auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

# Login
POST /api/v1/auth/login
{
  "email": "admin@domestika.local",
  "password": "admin123"
}

# Get Profile
GET /api/v1/auth/me
Authorization: Bearer {token}

# Refresh Token
POST /api/v1/auth/refresh
Authorization: Bearer {token}

# Logout
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

### System Endpoints

```bash
# Health Check
GET /api/v1/health

# Metrics (Prometheus format)
GET /api/v1/metrics
```

### Admin Endpoints

```bash
# List Users
GET /api/v1/admin/users
Authorization: Bearer {token}

# Get User
GET /api/v1/admin/users/{id}
Authorization: Bearer {token}

# List Roles
GET /api/v1/admin/roles
Authorization: Bearer {token}

# List Permissions
GET /api/v1/admin/permissions
Authorization: Bearer {token}
```

Full API documentation available at: **http://localhost:8000/api/documentation**

### Postman Collection

A complete Postman collection is available for testing all API endpoints:

- **Collection**: `Domestika_API.postman_collection.json`
- **Environment**: `Domestika_API.postman_environment.json`

**Features**:
- All API endpoints organized by category
- Automatic token management (save/refresh/clear)
- Pre-configured environment variables
- Request examples with descriptions
- Bearer token authentication

**Quick Start**:
```bash
1. Import both files into Postman
2. Select "Domestika API - Local" environment
3. Run Auth > Register or Auth > Login
4. Tokens will be saved automatically
5. Use any protected endpoint
```

See **[POSTMAN.md](POSTMAN.md)** for detailed documentation.

## 🧪 Testing

```bash
# Run all tests
make test

# Run with coverage
docker compose exec app php artisan test --coverage

# Run specific test
docker compose exec app php artisan test --filter=AuthenticationTest
```

## 🎨 Code Style

```bash
# Fix code style
make pint

# Check code style (without fixing)
docker compose exec app ./vendor/bin/pint --test
```

## 📊 Monitoring & Observability

### Grafana Dashboards

Access Grafana at http://localhost:3000 to view:
- Application performance metrics
- Database queries and connections
- Queue processing statistics
- API response times
- Error rates

### Logs

View logs with:
```bash
# All logs
make logs

# Application logs
make logs-app

# Nginx logs
make logs-nginx

# Queue worker logs
make logs-queue
```

Logs are also available in Grafana via Loki integration.

## 🔧 Development

### Database Management

```bash
# Create migration
docker compose exec app php artisan make:migration create_example_table

# Run migrations
make migrate

# Fresh migrations with seed
make migrate-fresh

# Rollback migration
make migrate-rollback

# Database backup
make db-backup

# Database restore
make db-restore FILE=backup.sql
```

### Queue Workers

```bash
# Start queue worker
make queue

# Restart queue workers
make queue-restart
```

### Cache Management

```bash
# Clear all caches
make cache-clear

# Optimize application
make optimize
```

## 🗂️ Project Structure

```
.
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/V1/
│   │   │       ├── Auth/           # Authentication controllers
│   │   │       ├── Admin/          # Admin controllers
│   │   │       └── BaseController.php
│   │   └── Requests/               # Form requests
│   └── Models/                     # Eloquent models
├── config/                         # Configuration files
├── database/
│   ├── migrations/                 # Database migrations
│   └── seeders/                    # Database seeders
├── docker/                         # Docker configuration
│   ├── php/                        # PHP-FPM Dockerfile
│   ├── nginx/                      # Nginx configuration
│   ├── prometheus/                 # Prometheus config
│   └── grafana/                    # Grafana dashboards
├── routes/
│   └── api.php                     # API routes
├── tests/                          # Tests
├── docker compose.yml              # Docker services
├── Makefile                        # Automation commands
└── README.md
```

## 🔒 Security Features

- JWT tokens with blacklist support
- Token refresh rotation
- Rate limiting per IP and user
- CORS configuration
- File upload validation
- Security headers (X-Frame-Options, X-XSS-Protection, etc.)
- Comprehensive audit logging
- API key authentication for system integrations

## 🌍 Environment Variables

Key environment variables in `.env`:

```env
APP_NAME="Domestika Laravel API"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_DATABASE=laravel_api
DB_USERNAME=laravel
DB_PASSWORD=secret

# JWT
JWT_SECRET=                         # Generated automatically
JWT_TTL=60                          # Token lifetime in minutes
JWT_REFRESH_TTL=20160               # Refresh token lifetime

# Queue
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
```

## 🐛 Troubleshooting

### Containers won't start

```bash
# Clean and rebuild
make clean
make install
```

### Permission issues

```bash
# Fix permissions
make permissions
```

### Database connection issues

```bash
# Check database logs
docker compose logs db

# Restart database
docker compose restart db
```

### JWT token issues

```bash
# Regenerate JWT secret
docker compose exec app php artisan jwt:secret
```

## 📝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Run tests (`make test`)
4. Fix code style (`make pint`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 🤝 Support

For issues and questions:
- Create an issue on GitHub
- Check the documentation at `/api/documentation`
- Review the CLAUDE.md file for development guidelines

## 🎉 Acknowledgments

Built with:
- [Laravel](https://laravel.com)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [JWT Auth](https://github.com/tymondesigns/jwt-auth)
- [L5 Swagger](https://github.com/DarkaOnLine/L5-Swagger)
- [Laravel Queue RabbitMQ](https://github.com/vyuldashev/laravel-queue-rabbitmq)

---

**Made with ❤️ by the Domestika team**
