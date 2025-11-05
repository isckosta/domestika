# Sistema de Créditos (D$) - Domestika API

## Visão Geral

Sistema completo de economia simbólica interna usando créditos (D$) para recompensar usuários, desbloquear funcionalidades e manter um histórico auditável de todas as transações.

## Arquitetura

```
Controller → Service → Model → Database
     ↓
  Policy (Authorization)
```

## Componentes Principais

### 1. Models

#### UserCredit
- Armazena o saldo atual de créditos de cada usuário
- Relacionamento one-to-one com User
- Campos: `user_id`, `balance`, `timestamps`

#### CreditTransaction
- Histórico imutável de todas as transações
- Hash SHA256 para auditoria
- Constraint UNIQUE (reference_id, user_id) para idempotência
- Campos: `user_id`, `amount`, `type`, `reason`, `reference_id`, `related_user_id`, `transaction_hash`, `metadata`, `created_at`

#### CreditRule
- Regras dinâmicas de recompensas por eventos
- Gerenciável via painel administrativo
- Campos: `event`, `amount`, `description`, `is_active`, `metadata`, `timestamps`

### 2. Services

#### CreditService
Serviço central com métodos:
- `getBalance(User $user)`: Obtém saldo atual
- `addCredits(User $user, int $amount, string $reason)`: Adiciona créditos
- `deductCredits(User $user, int $amount, string $reason)`: Deduz créditos
- `transferCredits(User $from, User $to, int $amount, string $reason)`: Transfere entre usuários
- `getTransactionHistory(User $user, int $limit)`: Histórico de transações
- `recalculateBalance(User $user)`: Recalcula saldo para verificação de integridade

**Características:**
- Usa `DB::transaction()` para atomicidade
- Usa `lockForUpdate()` para evitar race conditions
- Registra logs detalhados de todas as operações
- Gera hash SHA256 automático para cada transação

#### ReputationRewardService
Integração com sistema de reputação:
- `processReward(User $user, string $event)`: Processa recompensa por evento
- Métodos específicos para cada tipo de evento (positive_review, service_completed, etc.)
- Consulta `credit_rules` para valores dinâmicos
- Dispara automaticamente addCredits/deductCredits

### 3. API Endpoints

Base: `/api/v1/credits`

| Método | Endpoint | Autenticação | Autorização | Descrição |
|--------|----------|--------------|-------------|-----------|
| GET | `/balance` | JWT | User | Saldo atual |
| GET | `/transactions` | JWT | User | Histórico de transações |
| POST | `/add` | JWT | Admin | Adicionar créditos |
| POST | `/deduct` | JWT | User | Deduzir créditos |
| POST | `/transfer` | JWT | User | Transferir créditos |

### 4. Jobs Agendados

#### CheckCreditIntegrityJob
- Agendado: **Semanalmente aos domingos às 2h**
- Recalcula todos os saldos baseado no SUM(amount) de transactions
- Detecta e corrige discrepâncias automaticamente
- Registra ajustes em log e cria transaction de auditoria
- Usa chunking para processar grandes volumes

### 5. Eventos e Listeners

#### UserRewardEvent
Evento disparado quando usuário merece recompensa

#### ProcessRewardListener
- Processa recompensas automaticamente via fila
- Implementa `ShouldQueue` para processamento assíncrono
- Trata falhas com logs detalhados

## Tipos de Eventos de Recompensa

| Evento | Valor Padrão | Descrição |
|--------|--------------|-----------|
| `positive_review` | 50 D$ | Avaliação positiva (4-5 estrelas) |
| `service_completed` | 100 D$ | Serviço concluído com sucesso |
| `quick_response` | 25 D$ | Resposta em menos de 1 hora |
| `account_verified` | 200 D$ | Verificação completa da conta |
| `profile_completed` | 150 D$ | Perfil 100% completo |
| `first_service` | 300 D$ | Primeiro serviço na plataforma |
| `referral` | 500 D$ | Indicação de novo usuário ativo |

## Segurança e Consistência

### Transações Atômicas
```php
DB::transaction(function () {
    $creditAccount = UserCredit::where('user_id', $user->id)
        ->lockForUpdate()
        ->first();

    // Operações críticas aqui
});
```

### Idempotência
- Constraint `UNIQUE (reference_id, user_id)` previne duplicação
- Útil para retry de operações em caso de falha

### Auditoria
- Cada transação tem hash SHA256 único
- Logs estruturados em JSON com correlation-id
- Histórico imutável (sem updated_at em transactions)

## Uso no Código

### Adicionar Créditos
```php
use App\Services\CreditService;

$creditService = app(CreditService::class);
$transaction = $creditService->addCredits(
    user: $user,
    amount: 100,
    reason: 'Completed profile',
    referenceId: 'profile_completion_123',
    metadata: ['source' => 'profile_wizard']
);
```

### Disparar Recompensa Automática
```php
use App\Events\Reputation\UserRewardEvent;
use App\Models\CreditRule;

event(new UserRewardEvent(
    user: $user,
    event: CreditRule::EVENT_POSITIVE_REVIEW,
    metadata: ['rating' => 5, 'review_id' => 'abc123']
));
```

### Transferir Créditos
```php
$transactions = $creditService->transferCredits(
    from: $currentUser,
    to: $recipientUser,
    amount: 50,
    reason: 'Payment for unlock contact'
);
```

## Instalação e Configuração

### 1. Executar Migrations
```bash
php artisan migrate
```

### 2. Executar Seeders
```bash
php artisan db:seed --class=CreditRuleSeeder
```

### 3. Configurar Queue Worker
```bash
php artisan queue:work rabbitmq --queue=default
```

### 4. Configurar Cron (para jobs agendados)
```bash
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

## Testes

### Executar Testes
```bash
# Todos os testes
php artisan test

# Apenas testes de créditos
php artisan test --filter=Credit

# Teste específico
php artisan test tests/Unit/Services/CreditServiceTest.php
```

### Cobertura de Testes
- ✅ CreditService unit tests
- ✅ API endpoint tests
- ✅ Autorização e permissões
- ✅ Validação de inputs
- ✅ Transações atômicas
- ✅ Race conditions prevention

## Monitoramento

### Logs
Todos os eventos são registrados em `storage/logs/laravel.log` com:
- `credit.added` - Créditos adicionados
- `credit.deducted` - Créditos deduzidos
- `credit.transferred` - Transferência realizada
- `credit.integrity.check` - Verificação de integridade

### Métricas Prometheus
- `credits_transactions_total` - Total de transações
- `credits_balance_gauge` - Distribuição de saldos
- `credits_integrity_checks` - Verificações de integridade

## Roadmap Futuro

- [ ] Dashboard administrativo para gerenciar credit_rules
- [ ] Relatórios de economia de créditos
- [ ] Limitador de taxa por usuário
- [ ] Notificações de saldo baixo
- [ ] Sistema de créditos expiráveis (opcional)
- [ ] Integração com sistema de gamificação
- [ ] API pública para parceiros

## Suporte

Para dúvidas ou issues, consulte:
- Documentação principal: `DOMESTIKA.md`
- API docs: http://localhost:8000/api/documentation
- Logs: `storage/logs/laravel.log`
