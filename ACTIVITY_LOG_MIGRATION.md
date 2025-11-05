# Migração do Spatie Activity Log

## Problema
A tabela `activity_log` não existe porque a migration do pacote `spatie/laravel-activitylog` não foi publicada.

## Solução Recomendada: Publicar a Migration Oficial

Execute o comando dentro do container Docker:

```bash
docker-compose exec app php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

Isso copiará a migration oficial do pacote para `database/migrations/`.

Depois, execute a migration:

```bash
docker-compose exec app php artisan migrate
```

## Migration Manual Criada

Uma migration manual foi criada em `database/migrations/2025_01_15_000002_create_activity_log_table.php`.

**Se você publicar a migration oficial**, você pode:
1. Deletar a migration manual: `2025_01_15_000002_create_activity_log_table.php`
2. Usar a migration oficial publicada pelo pacote

## Estrutura Esperada

A migration oficial do Spatie Activity Log deve criar uma tabela com:

- `id` (bigIncrements)
- `log_name` (string, nullable)
- `description` (text)
- `subject_type` (string, nullable) - morph
- `subject_id` (bigInteger, nullable) - morph
- `causer_type` (string, nullable) - morph
- `causer_id` (bigInteger, nullable) - morph
- `properties` (json ou text, nullable)
- `batch_uuid` (string, nullable)
- `event` (string, nullable)
- `created_at` / `updated_at` (timestamps)

## Verificação

Após executar a migration, verifique:

```bash
docker-compose exec app php artisan tinker
>>> Schema::hasTable('activity_log');
# Deve retornar: true
```

