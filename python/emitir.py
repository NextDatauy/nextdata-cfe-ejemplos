"""Emite un CFE con la API de NextData (Python 3.9+, sin dependencias).

Uso: NEXTDATA_API_KEY=nd_test_... python python/emitir.py solicitudes/exportacion-121-bienes.json
"""
import base64
import json
import os
import sys
import urllib.error
import urllib.request

BASE = os.environ.get("NEXTDATA_API_URL", "https://api.nextdata.uy")
KEY = os.environ.get("NEXTDATA_API_KEY")
if not KEY:
    sys.exit("Definí NEXTDATA_API_KEY (empezá con una clave nd_test_).")
if len(sys.argv) < 2:
    sys.exit("Indicá el archivo de la solicitud, por ejemplo solicitudes/eticket-101.json")


def api(path, body=None):
    data = json.dumps(body).encode() if body is not None else None
    req = urllib.request.Request(
        BASE + path,
        data=data,
        method="POST" if body is not None else "GET",
        headers={"Authorization": f"Bearer {KEY}", "Content-Type": "application/json"},
    )
    try:
        with urllib.request.urlopen(req, timeout=30) as res:
            return json.load(res)
    except urllib.error.HTTPError as err:
        detail = json.loads(err.read() or b"{}")
        # 401: clave inactiva. 409/422: leé "message". No cambies la idempotencyKey para reintentar.
        sys.exit(f"NextData {err.code}: {detail.get('message', detail)}")


with open(sys.argv[1], encoding="utf-8") as f:
    solicitud = json.load(f)

ready = api("/v1/dev/ready")
print("Ambiente:", ready.get("environment"), "· puede emitir:", ready.get("canIssue"))
if not ready.get("canIssue"):
    sys.exit("Completá certificado, CAE y datos de la empresa antes de emitir.")

cfe = api("/v1/dev/invoices/issue", solicitud)
print("Emitido · estado:", cfe.get("status"))

# El PDF oficial (firmado, con CAE y QR) viene en la misma respuesta.
if cfe.get("pdfBase64"):
    nombre = cfe.get("pdfFileName") or f"CFE_{cfe.get('outboxId')}.pdf"
    with open(nombre, "wb") as f:
        f.write(base64.b64decode(cfe["pdfBase64"]))
    print("PDF guardado en", nombre)
