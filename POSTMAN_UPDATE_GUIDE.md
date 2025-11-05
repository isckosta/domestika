# Guia para Atualizar Collections Postman

Este guia explica como manter as collections Postman atualizadas quando novos endpoints forem adicionados à API.

## Estrutura da Collection

A collection segue este padrão:

```json
{
  "info": { ... },
  "item": [
    {
      "name": "Categoria",
      "item": [
        {
          "name": "Nome do Endpoint",
          "request": { ... }
        }
      ]
    }
  ]
}
```

## Adicionando Novo Endpoint

### 1. Identificar a Categoria

Categorias existentes:
- **Auth** - Autenticação
- **Credits (D$)** - Sistema de créditos
- **Admin** - Gestão administrativa
- **System** - Monitoramento

### 2. Template de Endpoint

```json
{
  "name": "Nome do Endpoint",
  "request": {
    "auth": {
      "type": "bearer",
      "bearer": [
        {
          "key": "token",
          "value": "{{access_token}}",
          "type": "string"
        }
      ]
    },
    "method": "GET|POST|PUT|PATCH|DELETE",
    "header": [
      {
        "key": "Accept",
        "value": "application/json"
      },
      {
        "key": "Content-Type",
        "value": "application/json"
      }
    ],
    "body": {
      "mode": "raw",
      "raw": "{...}"
    },
    "url": {
      "raw": "{{base_url}}/endpoint/path",
      "host": ["{{base_url}}"],
      "path": ["endpoint", "path"],
      "query": [
        {
          "key": "param",
          "value": "value",
          "description": "Description"
        }
      ]
    },
    "description": "Detailed description..."
  },
  "response": []
}
```

### 3. Endpoints com Scripts

Para endpoints que devem salvar tokens ou variáveis:

```json
{
  "name": "Login",
  "event": [
    {
      "listen": "test",
      "script": {
        "exec": [
          "if (pm.response.code === 200) {",
          "    const jsonData = pm.response.json();",
          "    pm.environment.set('access_token', jsonData.data.access_token);",
          "}"
        ],
        "type": "text/javascript"
      }
    }
  ],
  "request": { ... }
}
```

## Exemplo: Adicionar Novo Endpoint de Credits

### Endpoint: Get Credit Rules (Admin)

```json
{
  "name": "Get Credit Rules (Admin)",
  "request": {
    "auth": {
      "type": "bearer",
      "bearer": [
        {
          "key": "token",
          "value": "{{access_token}}",
          "type": "string"
        }
      ]
    },
    "method": "GET",
    "header": [
      {
        "key": "Accept",
        "value": "application/json"
      }
    ],
    "url": {
      "raw": "{{base_url}}/admin/credit-rules?page=1&per_page=20",
      "host": ["{{base_url}}"],
      "path": ["admin", "credit-rules"],
      "query": [
        {
          "key": "page",
          "value": "1",
          "description": "Page number"
        },
        {
          "key": "per_page",
          "value": "20",
          "description": "Items per page"
        }
      ]
    },
    "description": "Get paginated list of credit rules.\n\n**Authentication**: Required (Bearer Token + Admin Role)\n\n**Response**: List of credit rules with events and amounts"
  },
  "response": []
}
```

## Atualizando a Collection Manualmente

### Via Postman UI

1. Abrir Postman
2. Navegar até a collection
3. Clicar com botão direito na pasta/categoria
4. Selecionar **Add Request**
5. Configurar o endpoint
6. Exportar collection atualizada:
   - Click direito na collection → **Export**
   - Selecionar **Collection v2.1**
   - Salvar sobre o arquivo existente

### Via Editor JSON

1. Abrir `Domestika_API.postman_collection.json` em um editor
2. Localizar a seção `"item": [...]`
3. Adicionar novo objeto no array apropriado
4. Salvar arquivo
5. Re-importar no Postman

## Boas Práticas

### 1. Nomenclatura Consistente

- Use nomes descritivos: "Get User Balance", "Add Credits (Admin)"
- Indique restrições: "(Admin)", "(Premium)"
- Use verbos no infinitivo: "Get", "Create", "Update", "Delete"

### 2. Descrições Completas

```markdown
Description format:
Brief one-line description.

**Authentication**: Required|Not Required (Bearer Token [+ Role])

**Request Body**: (if applicable)
- `field` (type, required/optional): Description
- ...

**Query Parameters**: (if applicable)
- `param` (type, optional): Description

**Response**:
- Description of response structure

**Errors**: (if applicable)
- 401: Unauthorized
- 422: Validation error
- ...

**Notes**: (if applicable)
- Important information
```

### 3. Variáveis de Environment

Sempre use variáveis do environment:
- `{{base_url}}` - URL base
- `{{access_token}}` - Token JWT
- `{{user_uuid}}` - UUID de usuário
- Crie novas variáveis conforme necessário

### 4. Valores de Exemplo

Use valores realistas nos bodies:
```json
{
  "amount": 100,
  "reason": "Unlock contact: John Doe",
  "reference_id": "contact_unlock_abc123",
  "metadata": {
    "contact_id": "9a8b7c6d-5e4f-3a2b-1c0d-9e8f7a6b5c4d"
  }
}
```

## Versionamento

### Quando Criar Nova Collection

Criar nova collection quando:
- API muda de versão (v1 → v2)
- Breaking changes grandes
- Múltiplos ambientes diferentes

### Como Versionar

```
Domestika_API_v1.postman_collection.json
Domestika_API_v2.postman_collection.json
```

## Sincronização com Swagger

### Gerar Collection do Swagger

```bash
# Gerar documentação Swagger
php artisan l5-swagger:generate

# Acessar
http://localhost:8000/api/documentation

# Exportar para Postman
# (Usar botão "Export" no Swagger UI)
```

### Mesclar com Collection Existente

1. Exportar do Swagger
2. Comparar endpoints novos
3. Adicionar manualmente à collection principal
4. Manter scripts e variáveis customizadas

## Checklist de Atualização

Ao adicionar novo endpoint, verificar:

- [ ] Nome descritivo
- [ ] Categoria correta
- [ ] Método HTTP correto
- [ ] Autenticação configurada (se necessário)
- [ ] Headers completos (Accept, Content-Type)
- [ ] Body de exemplo (se POST/PUT/PATCH)
- [ ] Query parameters documentados
- [ ] Path variables usando variáveis do environment
- [ ] Descrição completa com Markdown
- [ ] Scripts de Test (se necessário salvar variáveis)
- [ ] Testado manualmente no Postman
- [ ] Collection exportada e commitada no Git

## Exemplos de Scripts Úteis

### Salvar ID da Resposta

```javascript
if (pm.response.code === 201 || pm.response.code === 200) {
    const jsonData = pm.response.json();
    pm.environment.set('created_resource_id', jsonData.data.id);
    console.log('Resource ID saved: ' + jsonData.data.id);
}
```

### Validar Resposta

```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has required fields", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
    pm.expect(jsonData).to.have.property('data');
});

pm.test("Balance is a number", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data.balance).to.be.a('number');
});
```

### Limpar Variáveis

```javascript
// Ao fazer logout ou resetar
pm.environment.unset('access_token');
pm.environment.unset('refresh_token');
pm.environment.unset('user_uuid');
```

## Mantendo Documentação Sincronizada

Ao atualizar collection:

1. Atualizar `POSTMAN_GUIDE.md` se necessário
2. Atualizar `README.md` com novos endpoints
3. Commitar ambos no Git:
   ```bash
   git add Domestika_API.postman_collection.json
   git add Domestika_API.postman_environment.json
   git add POSTMAN_GUIDE.md
   git commit -m "feat: add new credit endpoints to Postman collection"
   ```

## Recursos

- [Postman Learning Center](https://learning.postman.com/)
- [Collection Format v2.1](https://schema.postman.com/json/collection/v2.1.0/)
- [Scripts Reference](https://learning.postman.com/docs/writing-scripts/script-references/postman-sandbox-api-reference/)
- [Environment Variables](https://learning.postman.com/docs/sending-requests/variables/)

## Suporte

Para dúvidas sobre atualização da collection:
- Consulte este guia
- Veja exemplos em endpoints existentes
- Mantenha consistência com o padrão atual
