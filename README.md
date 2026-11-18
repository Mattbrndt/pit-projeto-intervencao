# PIT — Projeto de Intervenção

App simples para montar o documento do Projeto de Intervenção (PIT): escolhe a
situação-problema, preenche as seções exigidas (Objetivo, Revisão de Conceito,
Metodologia, Tempo, Procedimento, Resultado) e gera o documento pronto.

- **Frontend:** PHP puro (`frontend/`) — formulário, listagem e visualização.
- **Backend:** Python stdlib (`backend/app.py`) — API JSON, validação e persistência em arquivos.

Sem dependências externas: nada de Composer, nada de `pip install`.

## Rodar

```bash
# 1) backend (porta 8010)
python3 backend/app.py

# 2) frontend (porta 8080), em outro terminal
php -S 127.0.0.1:8080 -t frontend
```

Abra http://127.0.0.1:8080

Ou use o atalho: `./run.sh` (sobe os dois).

## API

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/health` | status do serviço |
| GET | `/api/projetos` | lista os projetos salvos |
| GET | `/api/projetos/{id}` | projeto completo + documento |
| POST | `/api/projetos` | cria o projeto (valida e devolve o documento) |

Exemplo:

```bash
curl -s -X POST http://127.0.0.1:8010/api/projetos \
  -H 'Content-Type: application/json' \
  -d '{"nome":"Matheus Brandt Nascimento","rgm":"42256305","situacao":"1",
       "situacao_texto":"...","objetivo":"...","revisao_conceito":"...",
       "metodologia":"...","tempo":"...","procedimento":"...","resultado":"..."}'
```

Erros de validação voltam em `422` com a lista de problemas por campo
(obrigatórios, RGM 6–12 dígitos, cada seção com no mínimo 20 caracteres).

## Estrutura

```
backend/app.py      API + validação + geração do documento
backend/data/       projetos salvos (JSON, ignorado pelo git)
frontend/config.php cliente HTTP do backend
frontend/index.php  formulário e lista
frontend/ver.php    documento de um projeto
frontend/estilo.css estilo
```

A URL do backend pode ser trocada por variável de ambiente `PIT_API_BASE`.
