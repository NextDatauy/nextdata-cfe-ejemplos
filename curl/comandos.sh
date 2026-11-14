# Comandos de cURL para la API de NextData. Corrélos de a uno.
# Antes: export NEXTDATA_API_KEY=nd_test_...

# 1. ¿La clave está activa y la empresa puede emitir?
curl -s https://api.nextdata.uy/v1/dev/ready \
  -H "Authorization: Bearer $NEXTDATA_API_KEY"

# 2. Ver tus CAE (el "id" de cada uno va como tenantCaeId en la solicitud).
curl -s https://api.nextdata.uy/v1/dev/cae \
  -H "Authorization: Bearer $NEXTDATA_API_KEY"

# 3. Emitir una e-Factura de exportación de servicios (editá antes el tenantCaeId).
curl -s https://api.nextdata.uy/v1/dev/invoices/issue \
  -H "Authorization: Bearer $NEXTDATA_API_KEY" \
  -H "Content-Type: application/json" \
  --data @solicitudes/exportacion-121-servicios.json

# 4. Consultar los últimos comprobantes y su estado en DGI.
curl -s "https://api.nextdata.uy/v1/dev/cfe/outbox?limit=20" \
  -H "Authorization: Bearer $NEXTDATA_API_KEY"
