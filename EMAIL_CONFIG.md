# Configuração de Email - Doméstika API

## 📧 Status da Implementação

✅ **Notificações implementadas:**
- Verificação de Email (`VerifyEmailNotification`)
- Reset de Senha (`ResetPasswordNotification`)
- Notificações de Service Requests (via Jobs)

✅ **Configuração:**
- User model configurado para usar notificações customizadas
- Notificações em fila (ShouldQueue) para processamento assíncrono
- Templates de email personalizados

## 🔧 Configuração no .env

### Desenvolvimento (Mailhog)

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@domestika.local
MAIL_FROM_NAME="Doméstika"
```

### Produção - SMTP Genérico

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@domestika.com
MAIL_FROM_NAME="Doméstika"
```

### Produção - AWS SES

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=noreply@domestika.com
MAIL_FROM_NAME="Doméstika"
```

## 📝 Variáveis Necessárias no .env

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@domestika.local
MAIL_FROM_NAME="Doméstika"
QUEUE_CONNECTION=rabbitmq
```

## 🚀 Como Testar

### 1. Usando Mailhog (Desenvolvimento)
```bash
docker-compose up -d mailhog
# Acesse http://localhost:8025 para ver emails capturados
```

### 2. Usando Log Driver
```env
MAIL_MAILER=log
```
Os emails serão salvos em `storage/logs/laravel.log`

### 3. Verificar Queue Worker
```bash
php artisan queue:work rabbitmq
```

## 📋 Notificações Implementadas

1. **Verificação de Email** - ✅ Implementado
2. **Reset de Senha** - ✅ Implementado
3. **Service Request Matched** - ✅ Implementado (via Job)

