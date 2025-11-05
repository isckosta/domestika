# 🏗️ Prompt: Infraestrutura Base para API Laravel Enterprise

Você é uma IA Arquiteta de Software responsável por criar a infraestrutura completa e estrutura base de uma API Laravel robusta, sem regras de negócio específicas.

## 🎯 **Objetivo**

Gerar um projeto Laravel com infraestrutura enterprise completa, pronto para receber qualquer domínio de negócio.

## ⚙️ **Stack Tecnológica Base**

### **Backend Core**

* **Framework:** Laravel 12 (PHP 8.3)
* **API:** REST com versionamento `/api/v1`
* **Autenticação:** JWT stateless com refresh token (tymon/jwt-auth)
* **Autorização:** RBAC via spatie/laravel-permission
* **Logs:** JSON estruturado com correlation-id

### **Banco de Dados & Cache**

* **Principal:** PostgreSQL 16
* **Cache/Session:** Redis 7
* **Extensões:** PostGIS (para geolocalização futura)

### **Mensageria & Queue**

* **Message Broker:** RabbitMQ 3.12 com Management UI
* **Queue Driver:** RabbitMQ (vladimir-yuldashev/laravel-queue-rabbitmq)

### **Documentação & API**

* **OpenAPI:** L5-Swagger 3.1 (darkaonline/l5-swagger)
* **Collection:** Postman auto-gerada
* **Formato de Erro:** Problem+JSON (RFC 7807)
* **Datas:** RFC 3339 padronizado

### **Infraestrutura Docker**

* **Web Server:** Nginx Alpine
* **PHP:** PHP-FPM 8.3 com extensões otimizadas
* **Orquestração:** Docker Compose completo

### **Observabilidade Completa**

* **Métricas:** Prometheus + Grafana
* **Logs:** Loki + Promtail + Grafana
* **Dashboards:** Pré-configurados para Laravel

### **Ferramentas de Desenvolvimento**

* **DB Admin:** pgAdmin 4
* **Cache UI:** Redis Insight
* **Email Testing:** Mailhog
* **File Storage:** MinIO (S3 compatible)
* **Container Management:** Portainer
* **Code Quality:** Laravel Pint + PHPUnit

## 🧱 **Estrutura Base Mínima**

### **Models Essenciais**

```php
// Apenas infraestrutura base
User (com traits: HasRoles, SoftDeletes, HasUuids)
Role, Permission (spatie/laravel-permission)
AuditLog (para auditoria)
ApiKey (para autenticação de sistemas)
```

### **Migrations Base**

* Users com UUID, soft deletes, timestamps
* Roles e permissions (spatie)
* Audit logs para rastreabilidade
* API keys para integração de sistemas

## 🚦 **Endpoints Mínimos de Infraestrutura**

### **Autenticação**

```
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
GET  /api/v1/auth/me
```

### **Sistema**

```
GET /api/v1/health
GET /api/v1/metrics
GET /api/v1/docs (Swagger UI)
```

### **Admin (RBAC)**

```
GET /api/v1/admin/users
GET /api/v1/admin/roles
GET /api/v1/admin/permissions
```

## 🧰 **Makefile Completo**

```makefile
.PHONY: help install install-fresh up down restart logs shell test pint swagger clean

help: ## Show help
install: build setup-env composer key-generate jwt-secret migrate seed swagger ## Full installation
install-fresh: clean build setup-env composer key-generate jwt-secret migrate-fresh swagger ## Fresh installation
up: ## Start containers
down: ## Stop containers
restart: ## Restart containers
logs: ## View logs
shell: ## Access app container
composer: ## Install dependencies
migrate: ## Run migrations
migrate-fresh: ## Fresh migrations with seed
seed: ## Run seeders
test: ## Run tests
pint: ## Code style
swagger: ## Generate docs
queue: ## Start queue worker
clean: ## Clean environment
cache-clear: ## Clear caches
optimize: ## Optimize Laravel
permissions: ## Fix permissions
```

## 📦 **Docker Compose Completo**

### **Serviços Essenciais**

```yaml
services:
  # Core Application
  app:
    build: ./docker/php/Dockerfile
    volumes: [./:/var/www]
    networks: [app-network]
    
  nginx:
    image: nginx:alpine
    ports: ["8000:80"]
    volumes: [./:/var/www, ./docker/nginx:/etc/nginx/conf.d]
    
  # Data Layer
  db:
    image: postgis/postgis:16-3.4
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    ports: ["5432:5432"]
    volumes: [postgres_data:/var/lib/postgresql/data]
    
  redis:
    image: redis:7-alpine
    ports: ["6379:6379"]
    volumes: [redis_data:/data]
    
  # Messaging
  rabbitmq:
    image: rabbitmq:3.12-management-alpine
    environment:
      RABBITMQ_DEFAULT_USER: ${RABBITMQ_USER}
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_PASSWORD}
    ports: ["5672:5672", "15672:15672"]
    volumes: [rabbitmq_data:/var/lib/rabbitmq]
    
  # Observability Stack
  grafana:
    image: grafana/grafana:latest
    environment:
      GF_SECURITY_ADMIN_USER: admin
      GF_SECURITY_ADMIN_PASSWORD: admin123
    ports: ["3000:3000"]
    volumes: [grafana_data:/var/lib/grafana]
    
  prometheus:
    image: prom/prometheus:latest
    ports: ["9090:9090"]
    volumes: [./docker/prometheus:/etc/prometheus]
    
  loki:
    image: grafana/loki:latest
    ports: ["3100:3100"]
    volumes: [loki_data:/loki]
    
  promtail:
    image: grafana/promtail:latest
    volumes: [./docker/promtail:/etc/promtail, /var/log:/var/log]
    
  # Development Tools
  pgadmin:
    image: dpage/pgadmin4:latest
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@example.com
      PGADMIN_DEFAULT_PASSWORD: admin123
    ports: ["5050:80"]
    
  redis-insight:
    image: redis/redisinsight:latest
    ports: ["5540:5540"]
    
  mailhog:
    image: mailhog/mailhog:latest
    ports: ["1025:1025", "8025:8025"]
    
  minio:
    image: minio/minio:latest
    command: server /data --console-address ":9001"
    environment:
      MINIO_ROOT_USER: minioadmin
      MINIO_ROOT_PASSWORD: minioadmin123
    ports: ["9000:9000", "9001:9001"]
    
  portainer:
    image: portainer/portainer-ce:latest
    ports: ["9443:9443"]
    volumes: [/var/run/docker.sock:/var/run/docker.sock]

volumes:
  postgres_data:
  redis_data:
  rabbitmq_data:
  grafana_data:
  loki_data:

networks:
  app-network:
    driver: bridge
```

## 🔧 **Configuração Base (.env.example)**

```bash
# Application
APP_NAME="Laravel API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=laravel_api
DB_USERNAME=laravel
DB_PASSWORD=secret

# Cache & Session
REDIS_HOST=redis
REDIS_PORT=6379
SESSION_DRIVER=redis
CACHE_STORE=redis

# Queue & Messaging
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=laravel
RABBITMQ_PASSWORD=secret

# JWT Authentication
JWT_SECRET=
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_BLACKLIST_ENABLED=true

# Security
CORS_ALLOWED_ORIGINS=*
RATE_LIMIT_PER_MINUTE=60
MAX_UPLOAD_SIZE=10240

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_FORMAT=json

# Mail (Development)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

# File Storage
FILESYSTEM_DISK=local
AWS_BUCKET=
AWS_REGION=
```

## 🧪 **Testes Base**

### **Estrutura de Testes**

```php
// Feature Tests
tests/Feature/Auth/AuthenticationTest.php
tests/Feature/Admin/HealthCheckTest.php
tests/Feature/Admin/UserManagementTest.php

// Unit Tests
tests/Unit/Services/AuthServiceTest.php
tests/Unit/Models/UserTest.php
```

## 📁 **Estrutura de Pastas**

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/
│   │   │   ├── Auth/
│   │   │   ├── Admin/
│   │   │   └── BaseController.php
│   │   └── Controller.php
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Services/
├── Exceptions/
└── Providers/

config/
├── auth.php
├── cors.php
├── jwt.php
├── permission.php
└── l5-swagger.php

database/
├── migrations/
├── seeders/
└── factories/

docker/
├── php/
│   ├── Dockerfile
│   └── php.ini
├── nginx/
│   └── default.conf
├── prometheus/
│   └── prometheus.yml
└── grafana/
    └── provisioning/

storage/
└── api-docs/
```

## 🔒 **Segurança Base**

### **Implementações Obrigatórias**

* JWT com blacklist e refresh rotation
* Rate limiting por IP e usuário
* CORS configurado
* Validação de uploads
* Headers de segurança
* Audit logging
* API key authentication para sistemas

## 📚 **Documentação Mínima**

### **Arquivos Essenciais**

* `README.md` - Setup e comandos
* `.env.example` - Variáveis documentadas
* `DEPLOYMENT.md` - Guia de deploy
* `API.md` - Documentação da API base

## 🚀 **Critérios de Aceite**

### **Infraestrutura**

* ✅ Docker Compose completo funcionando
* ✅ Makefile com automação completa
* ✅ Observabilidade configurada
* ✅ Autenticação JWT funcionando
* ✅ RBAC básico implementado
* ✅ Swagger UI acessível
* ✅ Testes base passando

### **Pronto para Desenvolvimento**

* ✅ Estrutura de pastas organizada
* ✅ Base controllers e services
* ✅ Middleware configurado
* ✅ Exception handling
* ✅ Logging estruturado
* ✅ Queue worker funcionando

## 🧩 **Entrega**

**Estrutura:** Projeto Laravel limpo com infraestrutura completa, sem regras de negócio específicas, pronto para receber qualquer domínio via `make install`.

**Foco:** Infraestrutura robusta, observabilidade, segurança e developer experience otimizada.

## 📋 **Composer Dependencies**

### **Produção**

```json
{
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0",
    "tymon/jwt-auth": "^2.1",
    "spatie/laravel-permission": "^6.0",
    "darkaonline/l5-swagger": "^8.6",
    "vladimir-yuldashev/laravel-queue-rabbitmq": "^14.0",
    "predis/predis": "^2.2",
    "guzzlehttp/guzzle": "^7.8"
  }
}
```

### **Desenvolvimento**

```json
{
  "require-dev": {
    "fakerphp/faker": "^1.23",
    "laravel/pint": "^1.24",
    "mockery/mockery": "^1.6",
    "phpunit/phpunit": "^11.5"
  }
}
```

## 🐳 **Dockerfile Base (PHP)**

```dockerfile
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    bcmath \
    gd \
    xml \
    zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

## 🌐 **Nginx Configuration**

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## 📊 **Prometheus Configuration**

```yaml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'laravel-app'
    static_configs:
      - targets: ['app:9000']
    metrics_path: '/metrics'
    scrape_interval: 30s
```

## 🎯 **Health Check Endpoint**

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp': now()->toISOString(),
        'services' => [
            'database' => DB::connection()->getPdo() ? 'ok' : 'error',
            'redis' => Redis::ping() ? 'ok' : 'error',
            'queue' => 'ok' // Add queue health check
        ]
    ]);
});
```
