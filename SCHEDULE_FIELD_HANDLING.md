# Análise: Tratamento do Campo `schedule` em Form-Data

## Contexto

O endpoint `POST /professionals` aceita requisições `multipart/form-data` para permitir upload de arquivos (foto). O campo `schedule` é um objeto JSON complexo que precisa ser enviado como string JSON no form-data, mas o validator espera um array.

## Solução Implementada

Adicionamos o método `prepareForValidation()` nos Form Requests (`CreateProfessionalRequest` e `UpdateProfessionalRequest`) para decodificar automaticamente o JSON string antes da validação.

```php
protected function prepareForValidation(): void
{
    if ($this->has('schedule') && is_string($this->schedule)) {
        try {
            $decoded = json_decode($this->schedule, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['schedule' => $decoded]);
            }
        } catch (\Exception $e) {
            // If decoding fails, leave as is and let validation handle it
        }
    }
}
```

## Vantagens ✅

1. **Flexibilidade**: Aceita tanto JSON string quanto array já decodificado
2. **Compatibilidade**: Funciona com form-data (necessário para upload de arquivos)
3. **Transparente**: O controller não precisa saber que houve conversão
4. **Seguro**: Validação de JSON antes de aplicar, com fallback para validação padrão
5. **Mantém API RESTful**: Não requer mudanças na estrutura da requisição
6. **Reutilizável**: Código centralizado no Form Request, seguindo padrão Laravel

## Desvantagens ⚠️

1. **Overhead de Processamento**: Decodificação JSON em cada requisição (mínimo impacto)
2. **Ocultação de Complexidade**: Pode não ser óbvio para novos desenvolvedores que o campo precisa ser JSON string
3. **Possível Confusão**: Se o cliente enviar array diretamente em JSON body, funcionará, mas em form-data precisa ser string
4. **Manutenção**: Requer atenção se a estrutura do schedule mudar
5. **Debugging**: Erros de JSON malformado podem ser menos claros

## Alternativas de Corpo de Requisição

### Opção 1: Form-Data com JSON String (Atual) ✅ RECOMENDADO

**Request:**
```
Content-Type: multipart/form-data

bio: "Sou profissional..."
skills[]: "limpeza_residencial"
skills[]: "organizacao"
schedule: {"monday":{"available":true,"hours":["08:00-18:00"]},...}
photo: [arquivo binário]
```

**Vantagens:**
- Permite upload de arquivos no mesmo request
- Mantém compatibilidade com formulários HTML
- Padrão comum em APIs REST

**Desvantagens:**
- Requer decodificação no backend (implementado)

---

### Opção 2: JSON Body com Upload Separado

**Request 1: Criar perfil**
```json
POST /api/v1/professionals
Content-Type: application/json

{
  "bio": "Sou profissional...",
  "skills": ["limpeza_residencial", "organizacao"],
  "schedule": {
    "monday": {"available": true, "hours": ["08:00-18:00"]}
  }
}
```

**Request 2: Upload de foto**
```
POST /api/v1/professionals/:id/photo
Content-Type: multipart/form-data

photo: [arquivo binário]
```

**Vantagens:**
- Estrutura mais clara e tipada
- Validação mais simples (sem decodificação)
- Melhor para APIs TypeScript/GraphQL
- Upload assíncrono de foto

**Desvantagens:**
- Requer dois requests separados
- Mais complexo no frontend
- Não é RESTful para upload único

---

### Opção 3: Base64 para Foto no JSON

**Request:**
```json
POST /api/v1/professionals
Content-Type: application/json

{
  "bio": "Sou profissional...",
  "skills": ["limpeza_residencial"],
  "schedule": {
    "monday": {"available": true, "hours": ["08:00-18:00"]}
  },
  "photo_base64": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Vantagens:**
- Tudo em JSON (consistente)
- Uma única requisição
- Sem necessidade de form-data

**Desvantagens:**
- Aumenta tamanho do payload (~33% maior que binário)
- Pode exceder limites de tamanho de request
- Processamento adicional no backend (decodificação base64)
- Não é ideal para arquivos grandes

---

### Opção 4: Form-Data com Campos Aninhados (Não Padrão)

**Request:**
```
Content-Type: multipart/form-data

bio: "Sou profissional..."
skills[]: "limpeza_residencial"
schedule[monday][available]: true
schedule[monday][hours][]: "08:00-18:00"
schedule[tuesday][available]: true
schedule[tuesday][hours][]: "09:00-17:00"
photo: [arquivo binário]
```

**Vantagens:**
- Não requer decodificação JSON
- Funciona nativamente com Laravel form-data

**Desvantagens:**
- Extremamente verboso
- Complexo para estruturas aninhadas
- Dificulta validação de tipos (tudo chega como string)
- Não é padrão REST

---

## Recomendação Final

**Manter a solução atual (Opção 1)** pelos seguintes motivos:

1. ✅ **Upload de arquivo**: Necessário para foto do perfil
2. ✅ **Simplicidade**: Um único request para criar perfil completo
3. ✅ **Padrão REST**: Form-data é padrão para multipart
4. ✅ **Compatibilidade**: Funciona com formulários HTML nativos
5. ✅ **Solução Elegante**: `prepareForValidation()` é padrão Laravel

**Melhorias Sugeridas:**

1. **Documentação**: Adicionar exemplo claro na documentação da API (Swagger/Postman)
2. **Validação Customizada**: Criar regra customizada `json_array` se necessário
3. **Helper Method**: Extrair lógica para trait se usado em múltiplos Form Requests
4. **Testes**: Adicionar testes unitários para validação de JSON string

---

## Exemplo de Teste

```php
public function test_schedule_json_string_is_decoded(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/professionals', [
            'bio' => str_repeat('a', 50),
            'skills' => ['cleaning'],
            'schedule' => json_encode([
                'monday' => ['available' => true, 'hours' => ['08:00-18:00']]
            ])
        ]);
    
    $response->assertStatus(201);
    $this->assertIsArray($response->json('data.schedule'));
}
```

---

## Conclusão

A solução implementada é **adequada e segue boas práticas Laravel**. O método `prepareForValidation()` é o hook correto para essa transformação de dados, mantendo a separação de responsabilidades e permitindo que a validação funcione normalmente.

A alternativa de separar upload de foto seria mais "limpa" em termos de tipos, mas sacrificaria a simplicidade e padrão REST da API atual.

