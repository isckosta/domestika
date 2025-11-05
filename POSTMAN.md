# Postman Collection - Domestika API

Collection completa para testar todos os endpoints da API Domestika Laravel.

## 📦 Arquivos

- **Domestika_API.postman_collection.json** - Collection principal com todos os endpoints
- **Domestika_API.postman_environment.json** - Variáveis de ambiente para desenvolvimento local

## 🚀 Importação

### 1. Importar a Collection

1. Abra o Postman
2. Clique em **Import** (canto superior esquerdo)
3. Selecione o arquivo `Domestika_API.postman_collection.json`
4. Clique em **Import**

### 2. Importar o Environment

1. Clique no ícone de **Environments** (canto superior direito)
2. Clique em **Import**
3. Selecione o arquivo `Domestika_API.postman_environment.json`
4. Clique em **Import**
5. Selecione o environment **"Domestika API - Local"** como ativo

## 📋 Estrutura da Collection

### 🔐 Auth
Endpoints de autenticação e gerenciamento de tokens JWT:

- **POST** `/auth/register` - Registrar novo usuário
- **POST** `/auth/login` - Login de usuário
- **GET** `/auth/me` - Obter dados do usuário autenticado
- **POST** `/auth/refresh` - Renovar access token
- **POST** `/auth/logout` - Fazer logout e invalidar token

### 👥 Admin > Users
Gerenciamento de usuários (requer autenticação):

- **GET** `/admin/users` - Listar usuários (com paginação e filtros)
- **GET** `/admin/users/{user_id}` - Exibir detalhes de um usuário
- **DELETE** `/admin/users/{user_id}` - Deletar usuário (soft delete)

### 🎭 Admin > Roles
Gerenciamento de roles RBAC:

- **GET** `/admin/roles` - Listar roles
- **GET** `/admin/roles/{role_id}` - Exibir detalhes de um role

### 🔑 Admin > Permissions
Gerenciamento de permissões:

- **GET** `/admin/permissions` - Listar todas as permissões

### 🏥 System
Endpoints de sistema e monitoramento:

- **GET** `/health` - Health check (DB, Redis, Queue)
- **GET** `/metrics` - Métricas Prometheus

## 🔧 Variáveis de Ambiente

| Variável | Descrição | Valor Padrão |
|----------|-----------|--------------|
| `base_url` | URL base da API | `http://localhost:8000/api/v1` |
| `access_token` | JWT access token | (gerado automaticamente) |
| `refresh_token` | JWT refresh token | (gerado automaticamente) |
| `user_uuid` | UUID do usuário para testes | (preencha manualmente) |
| `app_url` | URL da aplicação | `http://localhost:8000` |
| `api_version` | Versão da API | `v1` |

## 🎯 Fluxo de Uso

### 1. Autenticação Inicial

**Opção A: Registrar novo usuário**
```
1. Execute: Auth > Register
2. Os tokens serão salvos automaticamente nas variáveis de ambiente
```

**Opção B: Login com usuário existente**
```
1. Execute: Auth > Login
2. Os tokens serão salvos automaticamente nas variáveis de ambiente
```

### 2. Testar Autenticação

```
Execute: Auth > Get Current User
- Deve retornar seus dados com roles e permissions
```

### 3. Usar Endpoints Protegidos

Todos os endpoints em **Admin** requerem autenticação. O token é adicionado automaticamente usando a variável `{{access_token}}`.

### 4. Renovar Token

Quando o access token expirar (padrão: 60 minutos):
```
Execute: Auth > Refresh Token
- Novos tokens serão salvos automaticamente
```

## 🤖 Scripts Automáticos

A collection inclui scripts que executam automaticamente:

### Em Register/Login
```javascript
// Salva os tokens nas variáveis de ambiente
if (pm.response.code === 200 || pm.response.code === 201) {
    const jsonData = pm.response.json();
    pm.environment.set('access_token', jsonData.data.access_token);
    pm.environment.set('refresh_token', jsonData.data.refresh_token);
}
```

### Em Logout
```javascript
// Limpa os tokens das variáveis de ambiente
if (pm.response.code === 200) {
    pm.environment.unset('access_token');
    pm.environment.unset('refresh_token');
}
```

## 📝 Exemplos de Payloads

### Register
```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
}
```

### Login
```json
{
    "email": "john.doe@example.com",
    "password": "Password123!"
}
```

## 🔍 Query Parameters

### List Users
```
?page=1
&per_page=15
&search=john
&sort_by=created_at
&sort_order=desc
```

### List Roles/Permissions
```
?page=1
&per_page=15
```

## ⚙️ Configuração de Autenticação

Todos os endpoints protegidos usam **Bearer Token Authentication**:

```
Authorization: Bearer {{access_token}}
```

O token é adicionado automaticamente em cada request que requer autenticação.

## 🧪 Testando a Collection

### 1. Certifique-se que o projeto está rodando
```bash
make up
```

### 2. Verifique o health check
```
Execute: System > Health Check
Status esperado: 200 OK
```

### 3. Execute o fluxo completo
```
1. Register/Login
2. Get Current User
3. List Users
4. List Roles
5. List Permissions
6. Refresh Token
7. Logout
```

## 📊 Response Format

### Success Response
```json
{
    "data": {
        // Resource data
    },
    "message": "Success message",
    "correlation_id": "uuid-v4"
}
```

### Error Response (RFC 7807)
```json
{
    "type": "https://api.domestika.local/errors/validation",
    "title": "Validation Error",
    "status": 422,
    "detail": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    },
    "correlation_id": "uuid-v4"
}
```

## 🌐 Outros Ambientes

Para criar ambientes adicionais (staging, production):

1. Duplique o environment **"Domestika API - Local"**
2. Renomeie para o ambiente desejado
3. Atualize a variável `base_url` com a URL correta

## 🔒 Segurança

- **Não compartilhe** o arquivo de environment com tokens salvos
- Tokens são marcados como `secret` no Postman
- Use variáveis de ambiente para valores sensíveis
- Tokens expiram automaticamente (access: 60min, refresh: 14 dias)

## 📚 Documentação Adicional

- **Swagger UI**: http://localhost:8000/api/documentation
- **Health Check**: http://localhost:8000/api/v1/health
- **Metrics**: http://localhost:8000/api/v1/metrics

## 🐛 Troubleshooting

### Token Inválido ou Expirado
```
Solução: Execute Auth > Refresh Token ou faça login novamente
```

### 401 Unauthorized
```
Solução: Verifique se o environment está ativo e se o access_token está preenchido
```

### Connection Refused
```
Solução: Verifique se os containers estão rodando com 'make up'
```

### 429 Too Many Requests
```
Solução: Aguarde alguns minutos (rate limit: 60 req/min)
```

## 📞 Suporte

Para mais informações, consulte:
- `CLAUDE.md` - Instruções do projeto
- `DOMESTIKA.md` - Especificações completas
- `README.md` - Documentação geral
