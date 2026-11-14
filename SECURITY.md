# Seguridad

## Reportar una vulnerabilidad

Si encontrás un problema de seguridad en la API de NextData o en estos ejemplos, escribinos a **soporte@nextdata.uy** con el asunto «Seguridad». No abras un issue público. Respondemos dentro de las 24 horas.

## Buenas prácticas con tu clave

- Nunca subas tu clave (`nd_test_` o `nd_live_`) a un repositorio. Usá variables de entorno.
- Nunca subas tu certificado digital (PFX o P12). El `.gitignore` de este repositorio ya los excluye.
- Usá la clave de pruebas (`nd_test_`) hasta que tu integración funcione en ePrueba.
- Si una clave se expone, pedí que la den de baja a soporte@nextdata.uy y generá una nueva.
