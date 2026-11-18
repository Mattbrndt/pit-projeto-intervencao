# PIT II — Documentação, Codificação e Testes

**Nome:** Matheus Brandt Nascimento
**RGM:** 42256305

---

## Documentação

Documentação revisada e versionada no repositório (README com arquitetura, rotas da API,
regras de validação e instruções de execução).

**Link do repositório:** https://github.com/Mattbrndt/pit-projeto-intervencao

---

## Codificação

| Campo | Valor |
|---|---|
| Linguagem do Back-end | Python (stdlib, sem dependências externas) |
| Linguagem do Front-end | PHP |
| Banco de Dados | Persistência em arquivos JSON *(ver observação abaixo)* |
| Hospedagem | Execução local (`python3 backend/app.py` + `php -S`) |
| Plataforma | Web App Responsivo |
| Modo de Codificação | ( X ) Tradicional ( ) Low-code ( ) No-code |
| Link do repositório | https://github.com/Mattbrndt/pit-projeto-intervencao |
| Link da solução em funcionamento | *(pendente — exige deploy)* |
| Link do vídeo narrado | *(pendente — gravação)* |

> **Observação:** a tabela original indicava PostgreSQL e Low-code. O que está
> implementado e rodando é persistência em arquivo JSON e codificação tradicional.
> Ajustar o banco para PostgreSQL é uma troca pequena (a camada de persistência está
> isolada em `salvar()` / `listar()` / `carregar()`), mas enquanto não for feita, o
> campo precisa refletir o que existe.

---

## Testes da Solução

Bateria de testes funcionais e de robustez executada contra a aplicação rodando
(backend em `127.0.0.1:8010`, frontend em `127.0.0.1:8080`), em 17/11/2026.

### Rodada 1 — o que funcionou

| Caso | Resultado |
|---|---|
| `GET /api/health` | 200 `{"status":"ok"}` |
| Criação de projeto válido (10 campos) | 201, documento gerado |
| Validação de formulário incompleto | 422 com lista de 10 erros por campo |
| Listagem de projetos salvos | 200, item correto |
| `GET` de id inexistente na API | 404 |
| Envio pelo formulário PHP ponta a ponta | documento renderizado |
| Backend derrubado | front degradou com aviso, sem erro fatal |
| Escape de HTML no campo nome (`<script>`) | entrada neutralizada, sem XSS |
| `php -l` nos 3 arquivos PHP | sem erros de sintaxe |

### Rodada 1 — o que não funcionou (defeitos encontrados)

| # | Defeito | Evidência | Correção aplicada |
|---|---|---|---|
| 1 | Campo `nome` com 100.000 caracteres era **aceito e gravado** | `POST` retornou `201` | Limite de 120 caracteres no nome e 5.000 por seção; retorna `422` |
| 2 | Corpo de requisição sem limite — payload de 1 MB era lido inteiro na memória | `POST` 1 MB processado | Teto de 256 KB no corpo; retorna `413` |
| 3 | `DELETE` em rota existente devolvia `501 Not Implemented` | `DELETE /api/projetos` → `501` | `405 Method Not Allowed` em `DELETE` e `PUT` |
| 4 | `ver.php` com id inválido (`../../../etc/passwd`) respondia **HTTP 200** com página de erro | status 200 em recurso inexistente | `http_response_code()` passa a devolver `404` (id inválido) e `400` (id ausente) |
| 5 | Deprecation no PHP 8.5 (`$http_response_header`) poluía a saída | aviso no `php -l` | Uso de `http_get_last_response_headers()` com fallback |

### Rodada 2 — reteste após as correções

| # | Caso | Antes | Depois |
|---|---|---|---|
| 1 | Nome com 100k caracteres | 201 | **422** — "nome deve ter no maximo 120 caracteres" |
| 2 | Corpo de 1 MB | aceito | **413** |
| 3 | Seção com 6.000 caracteres | aceito | **422** — "Objetivo deve ter no maximo 5000 caracteres" |
| 4 | `DELETE /api/projetos` | 501 | **405** |
| 5 | `ver.php?id=../../../etc/passwd` | 200 | **404** |
| 6 | `ver.php` sem id | 200 | **400** |
| 7 | `POST` válido (não-regressão) | 201 | **201** |
| 8 | XSS no nome | escapado | **escapado** |
| 9 | Lint PHP + compilação Python | ok | **ok** |

Nenhuma funcionalidade existente regrediu: o caso 7 confirma que o fluxo
principal continua criando projetos normalmente.

### Funcionalidades não testadas (porque não estão implementadas)

- Autenticação e sessão de usuário
- Exportação do documento em PDF
- Edição e exclusão de projeto já salvo
- Teste de carga com usuários concorrentes
- Deploy em hospedagem pública

---

## Laudo de Qualidade

Foram executados testes funcionais, de validação de entrada e de robustez sobre o
web app do Projeto de Intervenção. O fluxo principal — preenchimento das seis seções
exigidas, validação, geração e consulta do documento — funcionou em todas as
execuções. Foram identificados **cinco defeitos**, nenhum deles no caminho feliz,
todos concentrados em tratamento de entrada abusiva e em códigos de status HTTP
incorretos. Correções aplicadas:

1. **Limites de tamanho de entrada:** o servidor aceitava campos arbitrariamente
   grandes (100 mil caracteres gravados em disco sem reclamar). Passou a validar
   120 caracteres para o nome e 5.000 por seção, devolvendo `422` com a mensagem
   do campo ofensor.

2. **Teto no corpo da requisição:** a API lia o corpo inteiro na memória sem limite.
   Foi imposto um teto de 256 KB, com resposta `413`, evitando consumo de memória
   por requisição maliciosa.

3. **Códigos de status corretos por método:** `DELETE` e `PUT` devolviam `501`,
   sugerindo recurso futuro. Passaram a devolver `405 Method Not Allowed`.

4. **Status correto em recurso inexistente:** a página de visualização respondia
   `200 OK` ao exibir "projeto não encontrado", o que engana cliente HTTP, cache e
   indexador. Agora devolve `404` para id inválido e `400` para id ausente.

5. **Compatibilidade com PHP 8.5:** substituída a variável mágica
   `$http_response_header`, depreciada, por `http_get_last_response_headers()`
   com fallback para versões anteriores.

Após as correções, a bateria completa foi reexecutada: os cinco defeitos deixaram de
se reproduzir e o fluxo principal seguiu íntegro. Commit das correções:
`92169b6` — "QA: limites de tamanho, 405 em metodo nao permitido e 404/400 correto em ver.php".

---

## Vídeo da Solução atualizada

| Link para o vídeo | *(pendente — gravação)* |
|---|---|
