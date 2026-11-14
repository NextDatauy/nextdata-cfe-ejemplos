// Emite un CFE con la API de NextData (Node.js 18+, sin dependencias).
// Uso: NEXTDATA_API_KEY=nd_test_... node node/emitir.mjs solicitudes/exportacion-121-servicios.json
import { readFile, writeFile } from "node:fs/promises";
import { basename } from "node:path";

const base = new URL(process.env.NEXTDATA_API_URL || "https://api.nextdata.uy");
const local = ["localhost", "127.0.0.1", "[::1]"].includes(base.hostname);
// La clave nunca viaja sin cifrar: solo https (o tu propia PC para pruebas locales).
if (base.protocol !== "https:" && !local) throw new Error("NEXTDATA_API_URL tiene que usar https.");

const key = process.env.NEXTDATA_API_KEY;
if (!key) throw new Error("Definí NEXTDATA_API_KEY (empezá con una clave nd_test_).");

const file = process.argv[2];
if (!file) throw new Error("Indicá el archivo de la solicitud, por ejemplo solicitudes/eticket-101.json");
const body = JSON.parse(await readFile(file, "utf8"));

async function api(path, init = {}) {
  const res = await fetch(new URL(path, base), {
    ...init,
    redirect: "error", // una redirección nunca recibe tu clave
    headers: { Authorization: `Bearer ${key}`, "Content-Type": "application/json", ...init.headers },
    signal: AbortSignal.timeout(30000),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    // 401: clave inactiva. 409/422: leé "message". No cambies la idempotencyKey para reintentar.
    throw new Error(`NextData ${res.status}: ${data.message ?? JSON.stringify(data)}`);
  }
  return data;
}

const ready = await api("/v1/dev/ready");
console.log("Ambiente:", ready.environment, "· puede emitir:", ready.canIssue);
if (!ready.canIssue) throw new Error("Completá certificado, CAE y datos de la empresa antes de emitir.");

const cfe = await api("/v1/dev/invoices/issue", { method: "POST", body: JSON.stringify(body) });
console.log("Emitido · estado:", cfe.status);

// El PDF oficial (firmado, con CAE y QR) viene en la misma respuesta.
if (cfe.pdfBase64) {
  // Solo el nombre del archivo: nunca se escribe fuera de la carpeta actual.
  const name = basename(String(cfe.pdfFileName || `CFE_${cfe.outboxId}.pdf`)).replace(/[^\w.-]/g, "_");
  await writeFile(name, Buffer.from(cfe.pdfBase64, "base64"), { mode: 0o600 });
  console.log("PDF guardado en", name);
}
// "pending" con "dgiSend: queued" es normal: el sobre sale a DGI enseguida. No emitas de nuevo.
