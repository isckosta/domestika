# Guia de Uso das Collections Postman - Domestika API

Este guia explica como importar e usar as collections Postman para testar a API Domestika.

## Arquivos Disponíveis

- `Domestika_API.postman_collection.json` - Collection completa com todos os endpoints
- `Domestika_API.postman_environment.json` - Environment com variáveis pré-configuradas

## Instalação

### 1. Importar no Postman

1. Abra o Postman
2. Clique em **Import** (botão no topo esquerdo)
3. Arraste os dois arquivos JSON para a janela de importação, ou clique em **Choose Files**
4. Selecione ambos os arquivos:
   - `Domestika_API.postman_collection.json`
   - `Domestika_API.postman_environment.json`
5. Clique em **Import**

### 2. Configurar Environment

1. No Postman, clique no dropdown de environments (canto superior direito)
2. Selecione **Domestika API - Local**
3. O environment está configurado com:
   - `base_url`: http://localhost:8000/api/v1
   - `access_token`: (será preenchido automaticamente após login)
   - `refresh_token`: (será preenchido automaticamente após login)
   - `user_uuid`: (você pode preencher manualmente se necessário)
   - `admin_token`: (para testes de admin)
   - `recipient_user_id`: (para transferências de crédito)

## Estrutura da Collection

A collection está organizada em 4 seções principais:

### 1. Auth (Autenticação)
- ✅ **Register** - Registrar novo usuário
- ✅ **Login** - Fazer login e obter JWT token
- ✅ **Get Current User** - Ver dados do usuário logado
- ✅ **Refresh Token** - Renovar token JWT
- ✅ **Logout** - Deslogar e invalidar token

### 2. Credits (D$) - Sistema de Créditos
- ✅ **Get Balance** - Ver saldo de créditos
- ✅ **Get Transaction History** - Histórico de transações
- ✅ **Add Credits (Admin)** - Adicionar créditos (somente admin)
- ✅ **Deduct Credits** - Deduzir créditos
- ✅ **Transfer Credits** - Transferir créditos para outro usuário

### 3. Admin - Gestão
- **Users**
  - ✅ List Users - Listar usuários com paginação e filtros
  - ✅ Show User - Ver detalhes de um usuário
  - ✅ Delete User - Deletar usuário (soft delete)
- **Roles**
  - ✅ List Roles - Listar roles
  - ✅ Show Role - Ver detalhes de uma role
- **Permissions**
  - ✅ List Permissions - Listar permissões

### 4. System - Monitoramento
- ✅ **Health Check** - Verificar status da aplicação
- ✅ **Metrics** - Métricas Prometheus

## Workflow de Uso

### Primeiro Uso (Setup Inicial)

1. **Iniciar a aplicação**
   ```bash
   make up
   ```

2. **Executar migrations e seeders**
   ```bash
   make migrate
   php artisan db:seed --class=CreditRuleSeeder
   ```

3. **No Postman, executar nesta ordem:**

   a. **Auth → Register**
   - Registra um novo usuário
   - O token será automaticamente salvo no environment
   
   b. **Auth → Get Current User**
   - Verifica que está autenticado
   - Anote o UUID do usuário

### Testando o Sistema de Créditos

#### Como Usuário Regular

1. **Credits (D$) → Get Balance**
   - Ver saldo atual (inicialmente 0 D$)

2. **Credits (D$) → Get Transaction History**
   - Ver histórico (vazio inicialmente)

#### Como Admin (Adicionar Créditos)

Para testar funcionalidades de admin, você precisa:

1. **Via Database/Tinker:**
   ```bash
   php artisan tinker
   ```
   ```php
   $user = User::where('email', 'seu@email.com')->first();
   $user->assignRole('admin');
   ```

2. **Fazer login novamente** para obter novo token com role admin

3. **Credits (D$) → Add Credits (Admin)**
   - Editar o body JSON:
     - `user_id`: UUID do usuário alvo
     - `amount`: 1000 (ou qualquer valor)
     - `reason`: "Initial balance"
   - Executar request
   - Créditos serão adicionados

#### Testando Operações de Crédito

1. **Deduzir Créditos:**
   - Executar **Deduct Credits**
   - Editar body:
     ```json
     {
       "amount": 50,
       "reason": "Unlock contact",
       "reference_id": "unique_ref_123"
     }
     ```
   - Ver novo saldo na resposta

2. **Transferir Créditos:**
   - Criar outro usuário via **Register** (use outro email)
   - Copiar UUID do novo usuário
   - Editar body de **Transfer Credits**:
     ```json
     {
       "to_user_id": "UUID_DO_DESTINATARIO",
       "amount": 100,
       "reason": "Payment for service"
     }
     ```
   - Executar request
   - Ver transações de saída e entrada

3. **Ver Histórico:**
   - Executar **Get Transaction History**
   - Ver todas as transações com hashes SHA256

## Scripts Automáticos

A collection possui scripts automáticos que:

### Login/Register
```javascript
// Salva tokens automaticamente
if (pm.response.code === 200 || pm.response.code === 201) {
    const jsonData = pm.response.json();
    pm.environment.set('access_token', jsonData.data.access_token);
    pm.environment.set('refresh_token', jsonData.data.refresh_token);
}
```

### Logout
```javascript
// Limpa tokens do environment
if (pm.response.code === 200) {
    pm.environment.unset('access_token');
    pm.environment.unset('refresh_token');
}
```

## Exemplos de Testes Completos

### Cenário 1: Novo Usuário Recebe Bonus

1. Register novo usuário → Token salvo automaticamente
2. Get Balance → 0 D$
3. (Admin) Add Credits:
   ```json
   {
     "user_id": "UUID_DO_USUARIO",
     "amount": 500,
     "reason": "Welcome bonus"
   }
   ```
4. Get Balance → 500 D$
5. Get Transaction History → 1 transação (credit)

### Cenário 2: Usuário Desbloqueia Contato

1. Login com usuário que tem saldo
2. Get Balance → Ex: 500 D$
3. Deduct Credits:
   ```json
   {
     "amount": 50,
     "reason": "Unlock contact: John Doe",
     "reference_id": "contact_unlock_abc123"
   }
   ```
4. Get Balance → 450 D$
5. Get Transaction History → Ver transação debit

### Cenário 3: Transferência Entre Usuários

1. Login usuário A (com saldo)
2. Criar usuário B via Register
3. Copiar UUID do usuário B
4. Transfer Credits:
   ```json
   {
     "to_user_id": "UUID_USUARIO_B",
     "amount": 100,
     "reason": "Payment for service"
   }
   ```
5. Get Balance usuário A → Saldo reduzido
6. Login usuário B
7. Get Balance usuário B → Saldo aumentado
8. Ver histórico de ambos → Transações transfer_out e transfer_in

## Variáveis do Environment

| Variável | Descrição | Como é preenchida |
|----------|-----------|-------------------|
| `base_url` | URL base da API | Pré-configurado |
| `access_token` | JWT token de acesso | Automaticamente no login |
| `refresh_token` | JWT refresh token | Automaticamente no login |
| `user_uuid` | UUID do usuário | Manualmente (copiar da resposta) |
| `admin_token` | Token de admin | Manualmente (após login como admin) |
| `recipient_user_id` | UUID destinatário | Manualmente (para transferências) |

## Troubleshooting

### 401 Unauthorized
- Verifique se o token está no environment
- Tente fazer login novamente
- Verifique se não expirou (TTL: 60 minutos)

### 403 Forbidden (Add Credits)
- Verifique se o usuário tem role 'admin'
- Atribuir role via tinker ou seeder

### 422 Validation Error
- Verifique os campos obrigatórios
- Verifique formato dos UUIDs
- Verifique valores numéricos (amount > 0)

### 500 Insufficient Balance
- Verificar saldo antes de deduzir/transferir
- Adicionar créditos via admin primeiro

## Recursos Adicionais

- **Documentação completa**: `CREDITS_MODULE.md`
- **Exemplos de código**: `CREDITS_USAGE_EXAMPLES.md`
- **API Swagger**: http://localhost:8000/api/documentation
- **Health Check**: http://localhost:8000/api/v1/health

## Dicas

1. **Runner do Postman**: Use o Collection Runner para executar testes em sequência
2. **Testes Automatizados**: Adicione assertions nos scripts de Test
3. **Ambientes Múltiplos**: Crie environments para dev, staging, production
4. **Versionamento**: Mantenha a collection no Git para compartilhar com a equipe
5. **Documentação**: Use a descrição dos endpoints como referência rápida

## Suporte

Para issues ou dúvidas:
- Consulte `CLAUDE.md` para overview do projeto
- Veja logs: `storage/logs/laravel.log`
- Execute testes: `php artisan test --filter=Credit`
