# Evidências de teste — PIT

Capturas e saídas obtidas com a aplicação em execução
(backend Python em `127.0.0.1:8010`, frontend PHP em `127.0.0.1:8080`), em 17/11/2026.

| Arquivo | O que comprova |
|---|---|
| `01-formulario.png` | Tela inicial com as 6 seções exigidas pelo PIT, nome, RGM e seletor de situação-problema |
| `02-validacao-erros.png` | Envio incompleto bloqueado: 10 mensagens de erro por campo, dados preservados no formulário |
| `03-documento-gerado.png` | Documento do PIT gerado com protocolo, data e as seis seções preenchidas |
| `04-documento-salvo.png` | Consulta de um projeto já salvo pela URL do protocolo |
| `05-responsivo-mobile.png` | Layout em viewport de 390 px, campos empilhados e inteiros |
| `06-lista-projetos.png` | Listagem dos projetos salvos com RGM, situação e data |
| `07-testes-api-terminal.png` | Saída real dos testes de API: 200, 422, 413, 405, 404 e 201 |

## Medição de responsividade

Overflow horizontal medido em viewport real de 390 px (iframe de mesma origem,
comparando `documentElement.scrollWidth` com `window.innerWidth`):

```
MEDIDA innerW=390 scrollW=375 overflow=NAO estourando=nenhum
```

Nenhum elemento ultrapassa a largura da tela.

## Como reproduzir

```bash
python3 backend/app.py 8010 &
php -S 127.0.0.1:8080 -t frontend
```

Os testes de API da captura 07 estão no README e no relatório `PIT_II_relatorio.md`.
