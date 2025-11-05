# Exemplos de Uso do Sistema de Créditos (D$)

## 1. Operações Básicas via API

### Consultar Saldo
```bash
curl -X GET http://localhost:8000/api/v1/credits/balance \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json"
```

**Resposta:**
```json
{
  "success": true,
  "message": "Balance retrieved successfully",
  "data": {
    "balance": 500,
    "user_id": "9d4e3a2b-1c5d-4f7e-8a9b-0c1d2e3f4a5b"
  }
}
```

### Adicionar Créditos (Admin)
```bash
curl -X POST http://localhost:8000/api/v1/credits/add \
  -H "Authorization: Bearer {ADMIN_JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "9d4e3a2b-1c5d-4f7e-8a9b-0c1d2e3f4a5b",
    "amount": 100,
    "reason": "Welcome bonus",
    "metadata": {
      "campaign": "new_user_2025"
    }
  }'
```

### Deduzir Créditos
```bash
curl -X POST http://localhost:8000/api/v1/credits/deduct \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50,
    "reason": "Unlock contact information",
    "reference_id": "contact_unlock_abc123"
  }'
```

### Transferir Créditos
```bash
curl -X POST http://localhost:8000/api/v1/credits/transfer \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "to_user_id": "7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d",
    "amount": 75,
    "reason": "Payment for service",
    "metadata": {
      "service_id": "srv_123456"
    }
  }'
```

### Ver Histórico de Transações
```bash
curl -X GET "http://localhost:8000/api/v1/credits/transactions?limit=20" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json"
```

## 2. Uso Programático no Backend

### Recompensar Usuário por Evento
```php
<?php

namespace App\Http\Controllers;

use App\Events\Reputation\UserRewardEvent;
use App\Models\CreditRule;
use App\Models\User;

class ServiceController extends Controller
{
    public function completeService($serviceId)
    {
        $service = Service::findOrFail($serviceId);
        $user = $service->professional;

        // Marcar serviço como completo
        $service->markAsCompleted();

        // Disparar evento de recompensa automática
        event(new UserRewardEvent(
            user: $user,
            event: CreditRule::EVENT_SERVICE_COMPLETED,
            metadata: [
                'service_id' => $service->id,
                'client_id' => $service->client_id,
                'value' => $service->value
            ]
        ));

        return response()->json([
            'message' => 'Service completed and reward triggered',
        ]);
    }
}
```

### Deduzir Créditos ao Desbloquear Contato
```php
<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\CreditService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function unlockContact(Request $request, CreditService $creditService)
    {
        $user = $request->user();
        $professional = User::findOrFail($request->professional_id);

        $unlockCost = 50; // D$ 50 para desbloquear contato

        try {
            // Deduzir créditos
            $transaction = $creditService->deductCredits(
                user: $user,
                amount: $unlockCost,
                reason: "Unlock contact: {$professional->name}",
                referenceId: "unlock_{$user->id}_{$professional->id}",
                metadata: [
                    'professional_id' => $professional->id,
                    'professional_name' => $professional->name,
                ]
            );

            // Registrar desbloqueio
            Contact::create([
                'user_id' => $user->id,
                'professional_id' => $professional->id,
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'success' => true,
                'contact' => [
                    'email' => $professional->email,
                    'phone' => $professional->phone,
                ],
                'remaining_balance' => $creditService->getBalance($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### Recompensa Manual por Ação Específica
```php
<?php

use App\Services\CreditService;
use App\Models\User;

$creditService = app(CreditService::class);
$user = User::find($userId);

// Adicionar créditos manualmente
$creditService->addCredits(
    user: $user,
    amount: 25,
    reason: 'Quick response to client message',
    metadata: [
        'response_time_seconds' => 120,
        'message_id' => 'msg_789',
    ]
);
```

## 3. Recompensas Automáticas por Eventos

### Verificação de Conta Completa
```php
<?php

namespace App\Http\Controllers;

use App\Events\Reputation\UserRewardEvent;
use App\Models\CreditRule;

class VerificationController extends Controller
{
    public function completeVerification(Request $request)
    {
        $user = $request->user();

        // Verificar documentos, email, telefone, etc.
        $user->markAsVerified();

        // Recompensar automaticamente
        event(new UserRewardEvent(
            user: $user,
            event: CreditRule::EVENT_ACCOUNT_VERIFIED,
            metadata: [
                'verification_date' => now()->toIso8601String(),
                'documents_verified' => true,
            ]
        ));

        return response()->json([
            'message' => 'Account verified! You earned 200 D$',
        ]);
    }
}
```

### Primeira Indicação (Referral)
```php
<?php

namespace App\Http\Controllers;

use App\Events\Reputation\UserRewardEvent;
use App\Models\CreditRule;
use App\Models\User;

class ReferralController extends Controller
{
    public function processReferralCompletion($referrerId, $newUserId)
    {
        $referrer = User::find($referrerId);
        $newUser = User::find($newUserId);

        // Verificar se novo usuário completou primeiro serviço
        if ($newUser->services()->completed()->count() > 0) {

            // Recompensar quem indicou
            event(new UserRewardEvent(
                user: $referrer,
                event: CreditRule::EVENT_REFERRAL,
                metadata: [
                    'referred_user_id' => $newUser->id,
                    'referred_user_name' => $newUser->name,
                ]
            ));

            return true;
        }

        return false;
    }
}
```

## 4. Verificação de Integridade Manual

### Executar Job Manualmente
```bash
php artisan tinker
```

```php
use App\Jobs\CheckCreditIntegrityJob;

// Executar imediatamente
dispatch_sync(new CheckCreditIntegrityJob());

// Ou agendar para fila
dispatch(new CheckCreditIntegrityJob());
```

### Recalcular Saldo Específico
```php
use App\Services\CreditService;
use App\Models\User;

$creditService = app(CreditService::class);
$user = User::find($userId);

// Saldo armazenado
$storedBalance = $user->creditAccount->balance;

// Saldo calculado
$calculatedBalance = $creditService->recalculateBalance($user);

if ($storedBalance !== $calculatedBalance) {
    echo "Discrepância detectada: $storedBalance != $calculatedBalance\n";
}
```

## 5. Gerenciar Regras de Crédito

### Criar Nova Regra
```php
use App\Models\CreditRule;

CreditRule::create([
    'event' => 'monthly_active_user',
    'amount' => 100,
    'description' => 'Reward for being active for 30 consecutive days',
    'is_active' => true,
    'metadata' => [
        'category' => 'retention',
        'min_days' => 30,
    ],
]);
```

### Desativar Regra
```php
$rule = CreditRule::findByEvent(CreditRule::EVENT_QUICK_RESPONSE);
$rule->is_active = false;
$rule->save();
```

### Atualizar Valor de Recompensa
```php
$rule = CreditRule::findByEvent(CreditRule::EVENT_POSITIVE_REVIEW);
$rule->amount = 75; // Aumentar de 50 para 75
$rule->save();
```

## 6. Monitoramento e Logs

### Verificar Logs de Créditos
```bash
tail -f storage/logs/laravel.log | grep -i credit
```

### Logs Importantes
```json
// Crédito adicionado
{
  "level": "info",
  "message": "Credits added",
  "context": {
    "user_id": "9d4e3a2b...",
    "amount": 100,
    "reason": "Service completed",
    "new_balance": 350,
    "transaction_id": "8c7d6e5f..."
  }
}

// Discrepância detectada
{
  "level": "warning",
  "message": "Credit balance discrepancy detected",
  "context": {
    "user_id": "9d4e3a2b...",
    "stored_balance": 300,
    "calculated_balance": 350,
    "difference": 50
  }
}
```

## 7. Troubleshooting

### Usuário reclama de saldo incorreto
```php
use App\Services\CreditService;
use App\Models\User;

$user = User::find($userId);
$creditService = app(CreditService::class);

// 1. Ver saldo atual
echo "Saldo atual: " . $creditService->getBalance($user) . "\n";

// 2. Recalcular baseado em transações
$calculated = $creditService->recalculateBalance($user);
echo "Saldo calculado: $calculated\n";

// 3. Ver últimas transações
$transactions = $creditService->getTransactionHistory($user, 10);
foreach ($transactions as $tx) {
    echo "{$tx->created_at}: {$tx->type} {$tx->amount} - {$tx->reason}\n";
}
```

### Transação duplicada (idempotência)
```php
// Se enviar o mesmo reference_id, a segunda tentativa falhará
try {
    $creditService->addCredits(
        user: $user,
        amount: 100,
        reason: 'Reward',
        referenceId: 'reward_unique_123'
    );
} catch (\Illuminate\Database\QueryException $e) {
    if ($e->getCode() === '23505') { // Unique violation
        echo "Esta transação já foi processada!\n";
    }
}
```

## 8. Boas Práticas

1. **Sempre use reference_id** para operações que podem falhar e ser reexecutadas
2. **Inclua metadata** relevante para auditoria futura
3. **Use eventos** para recompensas automáticas ao invés de chamar diretamente o serviço
4. **Verifique saldo** antes de permitir operações que consomem créditos
5. **Monitore logs** regularmente para detectar anomalias
6. **Execute integrity check** periodicamente ou após migrações de dados
