#!/usr/bin/env python3
"""API do PIT - Projeto de Intervencao.

Backend em Python puro (stdlib, zero dependencias).
Guarda os projetos em arquivos JSON e devolve o documento formatado.

Uso: python3 app.py [porta]   (default 8010)
"""

import json
import os
import re
import sys
import uuid
from datetime import datetime
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(BASE_DIR, "data")
os.makedirs(DATA_DIR, exist_ok=True)

SECOES = [
    ("objetivo", "Objetivo"),
    ("revisao_conceito", "Revisao de Conceito"),
    ("metodologia", "Metodologia"),
    ("tempo", "Tempo"),
    ("procedimento", "Procedimento e material utilizado"),
    ("resultado", "Resultado e discussao"),
]

SITUACOES = ["1", "2", "3"]

# Limites de tamanho: protegem o servico contra payload abusivo.
LIMITE_CORPO = 256 * 1024          # bytes aceitos no POST
LIMITE_CAMPO = 5000                # caracteres por secao
LIMITE_NOME = 120


def validar(payload):
    erros = []
    nome = (payload.get("nome") or "").strip()
    rgm = (payload.get("rgm") or "").strip()
    situacao = str(payload.get("situacao") or "").strip()

    if not nome:
        erros.append("nome e obrigatorio")
    elif len(nome) > LIMITE_NOME:
        erros.append("nome deve ter no maximo %d caracteres" % LIMITE_NOME)
    if not re.fullmatch(r"\d{6,12}", rgm):
        erros.append("rgm deve ter de 6 a 12 digitos")
    if situacao not in SITUACOES:
        erros.append("situacao deve ser 1, 2 ou 3")

    texto_situacao = (payload.get("situacao_texto") or "").strip()
    if not texto_situacao:
        erros.append("situacao_texto e obrigatorio")
    elif len(texto_situacao) > LIMITE_CAMPO:
        erros.append("situacao_texto deve ter no maximo %d caracteres" % LIMITE_CAMPO)

    for campo, rotulo in SECOES:
        texto = (payload.get(campo) or "").strip()
        if not texto:
            erros.append("%s e obrigatorio" % rotulo)
        elif len(texto) < 20:
            erros.append("%s precisa de pelo menos 20 caracteres" % rotulo)
        elif len(texto) > LIMITE_CAMPO:
            erros.append("%s deve ter no maximo %d caracteres" % (rotulo, LIMITE_CAMPO))
    return erros


def montar_documento(reg):
    linhas = [
        "PROJETO DE INTERVENCAO - PIT",
        "=" * 40,
        "Nome: %s" % reg["nome"],
        "RGM: %s" % reg["rgm"],
        "Situacao-problema escolhida: %s" % reg["situacao"],
        "",
        "A Situacao-Problema escolhida:",
        reg["situacao_texto"],
        "",
    ]
    for campo, rotulo in SECOES:
        linhas.append("%s:" % rotulo)
        linhas.append(reg[campo])
        linhas.append("")
    linhas.append("Gerado em %s" % reg["criado_em"])
    return "\n".join(linhas)


def salvar(payload):
    reg = {k: (payload.get(k) or "").strip() for k, _ in SECOES}
    reg.update(
        id=uuid.uuid4().hex[:12],
        nome=(payload.get("nome") or "").strip(),
        rgm=(payload.get("rgm") or "").strip(),
        situacao=str(payload.get("situacao") or "").strip(),
        situacao_texto=(payload.get("situacao_texto") or "").strip(),
        criado_em=datetime.now().strftime("%d/%m/%Y %H:%M"),
    )
    reg["documento"] = montar_documento(reg)
    with open(os.path.join(DATA_DIR, reg["id"] + ".json"), "w", encoding="utf-8") as fh:
        json.dump(reg, fh, ensure_ascii=False, indent=2)
    return reg


def listar():
    itens = []
    for nome_arq in os.listdir(DATA_DIR):
        if not nome_arq.endswith(".json"):
            continue
        with open(os.path.join(DATA_DIR, nome_arq), encoding="utf-8") as fh:
            reg = json.load(fh)
        itens.append(
            {
                "id": reg["id"],
                "nome": reg["nome"],
                "rgm": reg["rgm"],
                "situacao": reg["situacao"],
                "criado_em": reg["criado_em"],
            }
        )
    itens.sort(key=lambda i: i["criado_em"], reverse=True)
    return itens


def carregar(pid):
    caminho = os.path.join(DATA_DIR, os.path.basename(pid) + ".json")
    if not os.path.exists(caminho):
        return None
    with open(caminho, encoding="utf-8") as fh:
        return json.load(fh)


class Handler(BaseHTTPRequestHandler):
    server_version = "PIT-API/1.0"

    def _json(self, status, corpo):
        dados = json.dumps(corpo, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(dados)))
        self.end_headers()
        self.wfile.write(dados)

    def log_message(self, fmt, *args):
        sys.stderr.write("[api] %s - %s\n" % (self.address_string(), fmt % args))

    def do_GET(self):
        caminho = urlparse(self.path).path.rstrip("/")
        if caminho in ("/health", "/api/health"):
            return self._json(200, {"status": "ok"})
        if caminho == "/api/projetos":
            return self._json(200, {"itens": listar()})
        if caminho.startswith("/api/projetos/"):
            reg = carregar(caminho.rsplit("/", 1)[-1])
            if reg is None:
                return self._json(404, {"erro": "projeto nao encontrado"})
            return self._json(200, reg)
        return self._json(404, {"erro": "rota inexistente"})

    def do_DELETE(self):
        self._json(405, {"erro": "metodo nao permitido"})

    def do_PUT(self):
        self._json(405, {"erro": "metodo nao permitido"})

    def do_POST(self):
        caminho = urlparse(self.path).path.rstrip("/")
        if caminho != "/api/projetos":
            return self._json(404, {"erro": "rota inexistente"})
        tamanho = int(self.headers.get("Content-Length") or 0)
        if tamanho > LIMITE_CORPO:
            return self._json(413, {"erro": "corpo maior que o limite permitido"})
        bruto = self.rfile.read(tamanho).decode("utf-8", "replace") or "{}"
        try:
            payload = json.loads(bruto)
        except ValueError:
            return self._json(400, {"erro": "json invalido"})
        if not isinstance(payload, dict):
            return self._json(400, {"erro": "json invalido"})
        erros = validar(payload)
        if erros:
            return self._json(422, {"erros": erros})
        return self._json(201, salvar(payload))


def main():
    porta = int(sys.argv[1]) if len(sys.argv) > 1 else 8010
    servidor = ThreadingHTTPServer(("127.0.0.1", porta), Handler)
    print("API do PIT em http://127.0.0.1:%d" % porta)
    servidor.serve_forever()


if __name__ == "__main__":
    main()
