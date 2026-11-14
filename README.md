# API de facturación electrónica de Uruguay (CFE · DGI) — ejemplos de NextData

[![Licencia MIT](https://img.shields.io/badge/licencia-MIT-blue.svg)](LICENSE)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-api.nextdata.uy-6BA539.svg)](https://api.nextdata.uy/openapi.json)
[![Proveedor habilitado DGI](https://img.shields.io/badge/DGI-proveedor%20habilitado-0B5FFF.svg)](https://www.efactura.dgi.gub.uy/)
[![Exportación 121-124](https://img.shields.io/badge/exportaci%C3%B3n-121%20a%20124-orange.svg)](https://www.nextdata.uy/api-facturacion-exportacion-uruguay)

Ejemplos listos para emitir comprobantes fiscales electrónicos (CFE) de Uruguay con la API REST de [NextData](https://www.nextdata.uy), proveedor habilitado por DGI (Next Data SAS, RUT 220961970011).

Con un solo `POST /v1/dev/invoices/issue` emitís:

| Código | Comprobante |
|---|---|
| 101 · 102 · 103 | e-Ticket y sus notas de crédito y débito |
| 111 · 112 · 113 | e-Factura y sus notas de crédito y débito |
| **121 · 122 · 123** | **e-Factura de exportación** y sus notas (bienes y servicios, en dólares u otra moneda) |
| **124** | **e-Remito de exportación** |

El servidor arma, firma y envía el XML a DGI con tu certificado y tu CAE. Vos mandás JSON; nunca XML.

## Por qué NextData

- **Ambiente de pruebas real:** con una clave `nd_test_` los comprobantes viajan a ePrueba de DGI, sin efecto fiscal. Cuando todo sale bien, pasás a `nd_live_`.
- **Exportación por API:** 121 a 124 con el mismo endpoint, la misma clave y cotización oficial automática.
- **PDF oficial en la respuesta:** el 200 trae el comprobante firmado, con CAE y QR, listo para mandarle al cliente.
- **Idempotencia:** reintentar con la misma `idempotencyKey` nunca duplica un comprobante.
- **Servidor MCP:** Claude, ChatGPT o Cursor pueden emitir comprobantes por vos. Ver [MCP](https://www.nextdata.uy/mcp-facturacion-electronica).
- **También sin programar:** panel web, app para Android y facturación por WhatsApp gratis las 24 horas.

Precios de la API desde $ 890 por mes con IVA incluido: [nextdata.uy/api-facturacion-electronica-uruguay](https://www.nextdata.uy/api-facturacion-electronica-uruguay).

## Empezar en 3 pasos

1. **Pedí tu clave de pruebas** en [nextdata.uy/desarrolladores](https://www.nextdata.uy/desarrolladores#solicitar).
2. **Prepará la empresa:** certificado, datos fiscales y CAE de testing (`/v1/dev/certificate`, `/v1/dev/config/company`, `/v1/dev/cae`). La guía lo explica paso a paso.
3. **Emití:** elegí una solicitud de la carpeta [`solicitudes/`](solicitudes) y corré el ejemplo en tu lenguaje.

```bash
export NEXTDATA_API_URL=https://api.nextdata.uy
export NEXTDATA_API_KEY=nd_test_...        # nunca la subas al repositorio
node node/emitir.mjs solicitudes/exportacion-121-servicios.json
```

## Contenido

| Carpeta | Qué hay |
|---|---|
| [`solicitudes/`](solicitudes) | Cuerpos JSON de ejemplo: e-Ticket, e-Factura, exportación de bienes y de servicios, nota de crédito de exportación |
| [`node/`](node) | Node.js 18 o superior, sin dependencias |
| [`python/`](python) | Python 3.9 o superior, sin dependencias |
| [`php/`](php) | PHP 8 con cURL |
| [`curl/`](curl) | Comandos de cURL |

## Exportación: lo que cambia

- Receptor con `receiverCircuit: "exterior"`, `pais` en ISO de 2 letras distinto de `UY`, documento de su país y domicilio completo (`address`, `city`, `receptorState`, `receptorCountryName`).
- Bloque `exportacion` obligatorio: `clauVenta` (Incoterm o `N/A`), `modVenta` (`1` régimen general, `90` exportación de servicios…) y `viaTransp` (`1` marítimo, `2` aéreo, `3` terrestre, `8` no aplica).
- Servicios: `exportType: "EXPORT_SERVICES"` y `servicesConfirmed: true`.
- Todas las líneas con `ivaAliquota: 0`.
- Cada tipo de comprobante necesita su propio CAE.

## Respuestas que vas a ver

| Respuesta | Qué significa |
|---|---|
| 200 con `status: pending` y `dgiSend: queued` | Emitido. El PDF ya viene en `pdfBase64`; el sobre sale a DGI enseguida. **No emitas de nuevo.** |
| 401 | Clave inactiva o equivocada. |
| 409 con `retryAt` en `refresh-status` | DGI todavía no abrió la consulta de estado. Esperá esa hora. |
| 409 en el issue | Leé `message`: falta un dato de la empresa o de la sucursal. No consume numeración. |
| 422 | Falta completar el perfil de la empresa. |

## Documentación

- Guía completa: [nextdata.uy/desarrolladores](https://www.nextdata.uy/desarrolladores) ([versión Markdown](https://www.nextdata.uy/desarrolladores.md))
- Contrato OpenAPI: [api.nextdata.uy/openapi.json](https://api.nextdata.uy/openapi.json)
- API de exportación: [nextdata.uy/api-facturacion-exportacion-uruguay](https://www.nextdata.uy/api-facturacion-exportacion-uruguay)
- Comparativa de proveedores: [nextdata.uy/comparativa-facturacion-electronica-uruguay](https://www.nextdata.uy/comparativa-facturacion-electronica-uruguay)

## Preguntas frecuentes

**¿Cómo emito una factura electrónica en Uruguay desde mi sistema?**
Con un `POST /v1/dev/invoices/issue` y tu clave de NextData. El servidor arma el XML según los formatos vigentes de DGI, lo firma con tu certificado y lo envía. Vos solo mandás JSON.

**¿Puedo emitir la e-Factura de exportación (121) por API?**
Sí. Bienes y servicios, en dólares u otra moneda, con sus notas de crédito (122) y débito (123) y el e-Remito de exportación (124). Mirá [`solicitudes/exportacion-121-servicios.json`](solicitudes/exportacion-121-servicios.json).

**¿Hay ambiente de pruebas?**
Sí. Con una clave `nd_test_` los comprobantes van a ePrueba de DGI, sin efecto fiscal.

**¿Necesito ser emisor electrónico?**
Sí: certificado digital vigente, CAE para cada tipo de comprobante y NextData seleccionado como proveedor en DGI. La [guía de requisitos](https://www.nextdata.uy/requisitos-facturacion-electronica) explica cada paso.

**¿Quién manda el reporte diario a DGI?**
NextData lo genera y lo envía solo. No hay que programarlo.

**¿Se puede facturar con inteligencia artificial?**
Sí. El [servidor MCP](https://www.nextdata.uy/mcp-facturacion-electronica) deja que Claude, ChatGPT o Cursor emitan comprobantes con las mismas reglas.

## In English

Code samples for the [NextData](https://www.nextdata.uy) REST API, a DGI-authorized e-invoicing provider in Uruguay. Issue e-Tickets, e-Invoices and **export e-Invoices (CFE types 121–124)** with a single JSON request; the server builds, signs and submits the XML to DGI. Includes a DGI test environment (ePrueba), idempotent requests, the signed PDF in the response and an MCP server for AI agents. Docs: [nextdata.uy/desarrolladores](https://www.nextdata.uy/desarrolladores).

## Soporte

WhatsApp y correo las 24 horas: soporte@nextdata.uy. Todos los datos de estos ejemplos son ficticios.

Publicado por Next Data SAS bajo licencia [MIT](LICENSE).
