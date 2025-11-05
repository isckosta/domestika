# 📊 Relatório Técnico - Plataforma Doméstika (Back-end Laravel 12)

**Data:** 2025-01-XX  
**Versão API:** v1  
**Framework:** Laravel 12 (PHP 8.2+)  
**Tipo:** RESTful API

---

## 1. DIAGNÓSTICO GERAL DA PLATAFORMA

### 1.1 Status de Implementação da API

**Status Geral:** ⚠️ **PARCIALMENTE IMPLEMENTADO**

A API REST v1 está funcional com módulos core implementados, mas várias funcionalidades críticas do MVP expandido estão ausentes ou incompletas.

### 1.2 Arquitetura Atual

**Padrão Arquitetural:** ✅ **Controller → Service → Model → Policy**

- **Controllers:** Organizados em `app/Http/Controllers/Api/V1/`
- **Services:** Camada de lógica de negócio em `app/Services/`
- **Models:** Eloquent com traits (HasRoles, SoftDeletes, HasUuids)
- **Policies:** Implementadas para Credits e ServiceRequests
- **Versionamento:** ✅ `/api/v1` corretamente prefixado

**Avaliação da Arquitetura:**
- ✅ Modularidade adequada
- ✅ Separação de responsabilidades respeitada
- ✅ Versionamento API implementado
- ⚠️ Falta de Policies para alguns módulos (Chat, Reviews, Professionals)
- ⚠️ Middleware de segurança não totalmente configurado

### 1.3 Features Implementadas

#### ✅ **Autenticação e Autorização**
- Login/Registro com JWT (tymon/jwt-auth)
- Refresh token implementado
- Logout com invalidação de token
- Endpoint `/me` para dados do usuário autenticado

#### ✅ **Sistema de Créditos (D$)**
- CRUD completo de transações
- Balance tracking
- Transferência entre usuários
- Adição de créditos (admin)
- Dedução de créditos
- Histórico de transações
- Job de integridade de créditos (semanal)

#### ✅ **Direct Service Requests**
- Criação de solicitações de serviço
- Matching engine com embeddings (PGVector)
- Sistema de resposta de profissionais
- Status workflow (pending → matched → completed)
- Notificações por email

#### ✅ **Profissionais**
- Model Professional com reputação
- Sistema de badges e scores
- Embeddings de perfil (para matching)
- Skills e schedule

#### ✅ **Infraestrutura**
- Health check endpoint (`/health`)
- Metrics endpoint (`/metrics`)
- Swagger UI (L5-Swagger)
- Docker Compose completo
- Observabilidade (Prometheus + Grafana + Loki)
- Queue system (RabbitMQ)
- Audit logging (Spatie Activity Log)

### 1.4 Features Ausentes ou Incompletas

#### ❌ **Verificação de Identidade**
- Email verification: ❌ Não implementado (apenas campo `email_verified_at` existe)
- Verificação de documento: ❌ Não implementado
- Verificação por selfie: ❌ Não implementado
- **Impacto:** Alto risco de segurança e fraude

#### ❌ **Recuperação de Senha**
- Password reset flow: ❌ Não implementado
- Migrations existem (`password_reset_tokens`), mas endpoints não estão disponíveis
- **Impacto:** UX crítico ausente

#### ❌ **Chat/Mensageria**
- Model `ChatMessage` existe ✅
- Migrations criadas ✅
- **Endpoints:** ❌ Não implementados
- **Funcionalidades ausentes:**
  - Envio de mensagens
  - Listagem de conversas
  - Status de leitura
  - Notificações em tempo real

#### ❌ **Sistema de Reviews**
- Model `Review` existe ✅
- Migrations criadas ✅
- **Endpoints:** ❌ Não implementados
- **Funcionalidades ausentes:**
  - Criar review após serviço
  - Listar reviews de profissional
  - Sistema de rating (1-5 estrelas)
  - Atualização de reputação automática

#### ❌ **Ranking/Leaderboard**
- Sistema de reputação existe (campo `reputation_score`)
- **Endpoints:** ❌ Não implementados
- **Funcionalidades ausentes:**
  - Rankings por categoria
  - Top profissionais
  - Badges e conquistas
  - Histórico de ranking

#### ❌ **CMS (Content Management)**
- **Endpoints:** ❌ Não implementados
- **Funcionalidades ausentes:**
  - Gerenciamento de conteúdo estático
  - Categorias de serviços
  - Termos de uso e políticas
  - FAQs

#### ❌ **Admin Panel Completo**
- Endpoints básicos existem (users, roles, permissions)
- **Funcionalidades ausentes:**
  - Dashboard com métricas
  - Gerenciamento de Service Requests
  - Moderação de reviews
  - Gerenciamento de profissionais
  - Relatórios e analytics

#### ⚠️ **Perfis de Usuário**
- Model User básico implementado
- **Campos ausentes:**
  - Telefone
  - Endereço completo
  - Foto de perfil
  - Status de verificação
  - Informações de empresa (para role `company`)

---

## 2. ANÁLISE DE AUTENTICAÇÃO E AUTORIZAÇÃO

### 2.1 Sistema de Autenticação

#### ✅ **Implementação Atual**

**Pacote Utilizado:** `tymon/jwt-auth` (JWT)

**Endpoints Implementados:**
```
POST /api/v1/auth/register  ✅
POST /api/v1/auth/login     ✅
POST /api/v1/auth/logout    ✅
POST /api/v1/auth/refresh   ✅
GET  /api/v1/auth/me        ✅
```

**Configuração JWT:**
- ✅ TTL configurável (default: 60 minutos)
- ✅ Refresh TTL configurável (default: 20160 minutos / 2 semanas)
- ✅ Blacklist habilitado (`JWT_BLACKLIST_ENABLED=true`)
- ✅ Grace period configurável
- ✅ Algoritmo: HS256

**Gaps Identificados:**

1. **Refresh Token Rotation:** ❌ Não implementado
   - O refresh atual apenas gera novo token sem invalidar o anterior
   - Risco de segurança: tokens antigos podem ser reutilizados

2. **Rate Limiting:** ❌ Não configurado nos endpoints de autenticação
   - Login/registro vulneráveis a brute force
   - Recomendação: Implementar throttling (ex: 5 tentativas/minuto)

3. **Email Verification:** ❌ Não implementado
   - Usuários podem registrar sem verificar email
   - Campo `email_verified_at` existe mas não é validado

4. **Password Recovery:** ❌ Não implementado
   - Endpoints não existem
   - Migration `password_reset_tokens` existe mas não é utilizada

5. **Two-Factor Authentication (2FA):** ❌ Não implementado
   - Sem camada adicional de segurança

6. **IP-Based Blocking:** ❌ Não implementado
   - Sem proteção contra ataques de força bruta por IP
   - Sem blacklist de IPs suspeitos

7. **Login Attempts Tracking:** ❌ Não implementado
   - Sem histórico de tentativas de login
   - Sem bloqueio automático após múltiplas falhas

8. **JWT Custom Claims:** ⚠️ Vazio
   - Método `getJWTCustomClaims()` retorna array vazio
   - Oportunidade perdida: incluir roles/permissions no token

### 2.2 Sistema de Autorização (RBAC)

#### ✅ **Implementação Atual**

**Pacote Utilizado:** `spatie/laravel-permission` v6.0

**Middleware Configurado:**
```php
'role' => \Spatie\Permission\Middleware\RoleMiddleware::class
'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class
'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class
```

**Roles Existentes:**
- ✅ `admin` - Todas as permissões
- ✅ `moderator` - Permissões limitadas (view, update)
- ✅ `user` - Apenas view de usuários

**Permissões Existentes:**
- ✅ `users.*` (view, create, update, delete)
- ✅ `roles.*` (view, create, update, delete)
- ✅ `permissions.*` (view, create, update, delete)
- ✅ `audit-logs.view`
- ✅ `api-keys.*` (view, create, update, delete)

**Gaps Críticos Identificados:**

#### ❌ **Roles Ausentes para Domínio de Negócio**

**Roles Necessárias (não implementadas):**
1. **`contractor`** - Usuários que solicitam serviços
   - Permissões: `service-requests.create`, `service-requests.view`, `service-requests.cancel`

2. **`professional`** - Profissionais que prestam serviços
   - Permissões: `service-requests.respond`, `professionals.update`, `professionals.view`

3. **`company`** - Empresas/Organizações
   - Permissões: `professionals.create`, `professionals.manage`, `service-requests.bulk`

#### ❌ **Permissões Ausentes**

**Permissões de Service Requests:**
- `service-requests.create`
- `service-requests.view`
- `service-requests.update`
- `service-requests.cancel`
- `service-requests.complete`
- `service-requests.respond`

**Permissões de Professionals:**
- `professionals.create`
- `professionals.update`
- `professionals.view`
- `professionals.delete`
- `professionals.manage`

**Permissões de Credits:**
- `credits.view`
- `credits.deduct`
- `credits.transfer`
- `credits.add` (admin)

**Permissões de Reviews:**
- `reviews.create`
- `reviews.view`
- `reviews.update`
- `reviews.delete`
- `reviews.moderate` (admin)

**Permissões de Chat:**
- `chat.send`
- `chat.view`
- `chat.delete`

**Permissões de CMS:**
- `cms.view`
- `cms.create`
- `cms.update`
- `cms.delete`

### 2.3 Policies

#### ✅ **Policies Implementadas**

1. **`CreditPolicy`** ✅
   - `viewBalance()`
   - `addCredits()` - Admin only
   - `deductCredits()`
   - `transferCredits()`
   - `viewTransactions()`
   - `manageCreditRules()` - Admin only

2. **`ServiceRequestPolicy`** ✅
   - `view()`
   - `create()`
   - `update()`
   - `cancel()`
   - `complete()`
   - `respond()`

#### ❌ **Policies Ausentes**

1. **`ProfessionalPolicy`** - Não implementada
   - Necessária para gerenciar permissões de criação/edição de perfis profissionais

2. **`ReviewPolicy`** - Não implementada
   - Necessária para controlar criação/edição de reviews

3. **`ChatMessagePolicy`** - Não implementada
   - Necessária para controlar acesso às mensagens

4. **`UserPolicy`** - Não implementada
   - Necessária para gerenciar permissões de visualização/edição de perfis

### 2.4 Aplicação de Policies nos Controllers

#### ✅ **Uso Correto de Policies**

**`ServiceRequestController`:**
- ✅ `view()` verificado em `show()`
- ✅ `cancel()` verificado em `cancel()`
- ✅ `complete()` verificado em `complete()`
- ✅ `respond()` verificado em `respond()`

**`CreditController`:**
- ⚠️ **GAP:** Policies não são aplicadas diretamente nos métodos
- Verificação manual com `hasRole('admin')` em vez de usar Policy

#### ❌ **Gaps na Aplicação**

1. **`CreditController`:**
   - Não utiliza `$this->authorize()` em métodos
   - Depende apenas de middleware `role:admin`
   - Deveria usar Policies para lógica mais granular

2. **Métodos sem verificação:**
   - `CreditController::balance()` - Sem verificação de Policy
   - `CreditController::transactions()` - Sem verificação de Policy
   - `ServiceRequestController::index()` - Sem verificação de Policy

### 2.5 Inconsistências Identificadas

#### ⚠️ **Mapeamento Role-Permission-Business Logic**

1. **Role `user` vs `contractor` vs `professional`:**
   - Sistema atual usa apenas `user` genérico
   - Business logic diferencia entre contractor/professional baseado em relacionamento com `Professional` model
   - **Inconsistência:** Roles não refletem tipos de usuário do domínio

2. **Verificação de Professional Profile:**
   - `ServiceRequestPolicy::respond()` verifica se usuário tem `Professional` model
   - Não verifica role `professional`
   - **Problema:** Usuário pode ter role `user` mas ter `Professional` profile

3. **Atribuição Automática de Roles:**
   - Registro não atribui role automaticamente
   - `UserSeeder` atribui roles manualmente
   - **Gap:** Não há lógica para atribuir `contractor` ou `professional` baseado em ações

---

## 3. RECOMENDAÇÕES TÉCNICAS

### 3.1 Melhorias de Segurança

#### 🔒 **Autenticação**

1. **Implementar Refresh Token Rotation**
   ```php
   // Ao refrescar token, invalidar o anterior
   public function refresh(): JsonResponse
   {
       $currentToken = JWTAuth::getToken();
       $newToken = JWTAuth::refresh($currentToken);
       JWTAuth::invalidate($currentToken); // Invalidar token antigo
       
       return $this->respondWithToken($newToken);
   }
   ```

2. **Rate Limiting em Endpoints de Autenticação**
   ```php
   // routes/api.php
   Route::post('/auth/login', [AuthController::class, 'login'])
       ->middleware('throttle:5,1'); // 5 tentativas por minuto
   
   Route::post('/auth/register', [AuthController::class, 'register'])
       ->middleware('throttle:3,1'); // 3 tentativas por minuto
   ```

3. **Email Verification Flow**
   - Implementar `MustVerifyEmail` contract no User model
   - Criar endpoints:
     - `POST /api/v1/auth/email/verify`
     - `POST /api/v1/auth/email/resend`
   - Validar `email_verified_at` em endpoints críticos

4. **Password Recovery**
   - Implementar endpoints:
     - `POST /api/v1/auth/password/forgot`
     - `POST /api/v1/auth/password/reset`
   - Integrar com Laravel Password Reset

5. **IP-Based Blocking**
   - Criar middleware `BlockSuspiciousIPs`
   - Integrar com Redis para tracking de tentativas
   - Bloquear IP após X tentativas falhadas

6. **Login Attempts Tracking**
   - Criar model `LoginAttempt`
   - Registrar todas as tentativas (sucesso/falha)
   - Bloquear conta após 5 tentativas falhadas (15 minutos)

7. **JWT Custom Claims com Roles**
   ```php
   public function getJWTCustomClaims(): array
   {
       return [
           'roles' => $this->getRoleNames(),
           'permissions' => $this->getAllPermissions()->pluck('name'),
       ];
   }
   ```

#### 🔒 **Auditoria e Logging**

1. **Spatie Activity Log Completo**
   - ✅ Já implementado em alguns models (Professional, ServiceRequest)
   - ⚠️ Adicionar em User, CreditTransaction
   - Criar endpoints admin para visualizar logs:
     - `GET /api/v1/admin/audit-logs`
     - `GET /api/v1/admin/audit-logs/{id}`

2. **Audit Log para Autenticação**
   - Registrar todos os logins (sucesso/falha)
   - Registrar logout
   - Registrar password reset requests

### 3.2 Estrutura de Roles e Permissões

#### 📋 **Novas Roles Recomendadas**

```php
// database/seeders/RoleAndPermissionSeeder.php

// Roles de Negócio
$contractor = Role::create(['name' => 'contractor']);
$professional = Role::create(['name' => 'professional']);
$company = Role::create(['name' => 'company']);

// Permissões de Service Requests
Permission::create(['name' => 'service-requests.create']);
Permission::create(['name' => 'service-requests.view']);
Permission::create(['name' => 'service-requests.update']);
Permission::create(['name' => 'service-requests.cancel']);
Permission::create(['name' => 'service-requests.complete']);
Permission::create(['name' => 'service-requests.respond']);

// Permissões de Professionals
Permission::create(['name' => 'professionals.create']);
Permission::create(['name' => 'professionals.update']);
Permission::create(['name' => 'professionals.view']);

// Permissões de Credits
Permission::create(['name' => 'credits.view']);
Permission::create(['name' => 'credits.deduct']);
Permission::create(['name' => 'credits.transfer']);

// Atribuir permissões
$contractor->givePermissionTo([
    'service-requests.create',
    'service-requests.view',
    'service-requests.cancel',
    'service-requests.complete',
    'credits.view',
    'credits.deduct',
]);

$professional->givePermissionTo([
    'service-requests.view',
    'service-requests.respond',
    'professionals.create',
    'professionals.update',
    'professionals.view',
    'credits.view',
]);
```

### 3.3 Melhorias de Escalabilidade

#### 🚀 **Performance**

1. **Cache de Permissões**
   - Spatie Permission já faz cache, mas verificar configuração
   - Adicionar cache para queries frequentes (professionals, service requests)

2. **Database Indexing**
   - Verificar índices em tabelas críticas:
     - `service_requests.user_id`
     - `professionals.user_id`
     - `credit_transactions.user_id`

3. **Query Optimization**
   - Implementar eager loading em controllers:
     ```php
     ServiceRequest::with(['user', 'responses.professional'])
         ->where('user_id', $user->id)
         ->get();
     ```

4. **Queue Jobs Otimizados**
   - Matching engine já usa queue ✅
   - Considerar background jobs para:
     - Envio de emails (já implementado ✅)
     - Geração de embeddings (já implementado ✅)
     - Atualização de reputação (futuro)

#### 🚀 **Manutenibilidade**

1. **Service Layer Consistente**
   - ✅ Já implementado para CreditService, ServiceRequestService
   - Adicionar Services para:
     - `ReviewService`
     - `ChatService`
     - `ProfessionalService`
     - `AuthService` (extrair lógica de AuthController)

2. **Form Requests Consistentes**
   - ✅ Já implementado para Auth, Credits, ServiceRequests
   - Criar para Reviews, Chat, Professionals

3. **API Resources Consistentes**
   - ✅ Já implementado parcialmente
   - Completar para todos os models

### 3.4 Features Prioritárias para Implementação

#### 🔴 **Alta Prioridade**

1. **Email Verification**
   - Criticidade: Alta (segurança)
   - Esforço: Médio
   - Implementar endpoints e validação

2. **Password Recovery**
   - Criticidade: Alta (UX crítico)
   - Esforço: Médio
   - Implementar endpoints e emails

3. **Rate Limiting**
   - Criticidade: Alta (segurança)
   - Esforço: Baixo
   - Configurar middleware

4. **Roles e Permissões de Negócio**
   - Criticidade: Alta (arquitetura)
   - Esforço: Médio
   - Atualizar seeder e aplicar em controllers

#### 🟡 **Média Prioridade**

5. **Chat Endpoints**
   - Criticidade: Média (funcionalidade core)
   - Esforço: Alto
   - Implementar CRUD completo

6. **Review Endpoints**
   - Criticidade: Média (funcionalidade core)
   - Esforço: Médio
   - Implementar CRUD e atualização de reputação

7. **Refresh Token Rotation**
   - Criticidade: Média (segurança)
   - Esforço: Baixo
   - Atualizar método refresh

8. **Policies Faltantes**
   - Criticidade: Média (segurança)
   - Esforço: Baixo
   - Criar ProfessionalPolicy, ReviewPolicy, ChatMessagePolicy

#### 🟢 **Baixa Prioridade**

9. **Ranking/Leaderboard**
   - Criticidade: Baixa (nice-to-have)
   - Esforço: Médio
   - Endpoints de ranking

10. **CMS**
    - Criticidade: Baixa (nice-to-have)
    - Esforço: Alto
    - Sistema completo de gerenciamento de conteúdo

11. **Verificação de Identidade (Documento/Selfie)**
    - Criticidade: Baixa (futuro)
    - Esforço: Muito Alto
    - Requer integração com serviços externos

---

## 4. RESUMO EXECUTIVO

### ✅ **Pontos Fortes**

1. Arquitetura bem estruturada (Controller → Service → Model → Policy)
2. Infraestrutura robusta (Docker, Observabilidade, Queue)
3. Sistema de créditos completo e funcional
4. Direct Service Requests implementado com matching engine avançado
5. Uso adequado de Spatie Permission e Activity Log
6. Versionamento API correto (`/api/v1`)
7. Documentação Swagger implementada

### ⚠️ **Gaps Críticos**

1. **Segurança:**
   - Email verification não implementado
   - Password recovery ausente
   - Rate limiting não configurado
   - Refresh token rotation ausente
   - IP-based blocking ausente

2. **Autorização:**
   - Roles de negócio ausentes (contractor, professional, company)
   - Permissões específicas não mapeadas
   - Policies faltantes (Professional, Review, Chat)
   - Inconsistência entre roles e business logic

3. **Funcionalidades Core:**
   - Chat não implementado (endpoints)
   - Reviews não implementado (endpoints)
   - Ranking não implementado
   - CMS não implementado

### 🎯 **Recomendações Prioritárias**

1. **Imediato (1-2 semanas):**
   - Implementar email verification
   - Implementar password recovery
   - Configurar rate limiting
   - Criar roles e permissões de negócio
   - Implementar refresh token rotation

2. **Curto Prazo (1 mês):**
   - Implementar endpoints de Chat
   - Implementar endpoints de Reviews
   - Criar Policies faltantes
   - IP-based blocking

3. **Médio Prazo (2-3 meses):**
   - Sistema de Ranking
   - CMS básico
   - Admin panel completo
   - Verificação de identidade (se necessário)

---

## 5. CHECKLIST DE IMPLEMENTAÇÃO

### Segurança
- [ ] Email verification flow completo
- [ ] Password recovery (forgot/reset)
- [ ] Rate limiting em endpoints críticos
- [ ] Refresh token rotation
- [ ] IP-based blocking
- [ ] Login attempts tracking
- [ ] JWT custom claims com roles/permissions

### Autorização
- [ ] Roles: contractor, professional, company
- [ ] Permissões específicas de domínio
- [ ] ProfessionalPolicy
- [ ] ReviewPolicy
- [ ] ChatMessagePolicy
- [ ] UserPolicy
- [ ] Atribuição automática de roles no registro

### Funcionalidades
- [ ] Chat endpoints (CRUD)
- [ ] Review endpoints (CRUD)
- [ ] Ranking endpoints
- [ ] CMS endpoints
- [ ] Admin dashboard endpoints
- [ ] Professional profile endpoints

### Melhorias Técnicas
- [ ] Cache de queries frequentes
- [ ] Database indexing otimizado
- [ ] Eager loading em controllers
- [ ] Services para todos os módulos
- [ ] Form Requests consistentes
- [ ] API Resources completos

---

**Relatório gerado em:** 2025-01-XX  
**Versão:** 1.0  
**Autor:** Análise Técnica Automatizada

