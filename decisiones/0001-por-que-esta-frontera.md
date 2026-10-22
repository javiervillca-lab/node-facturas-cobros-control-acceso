# 0001. ¿Por qué esta frontera?

## Contexto

Se propone separar facturación y cobros en contextos diferentes.

## Decisión

Mantener una frontera de contexto entre la emisión de facturas y la gestión de pagos para aislar responsabilidades y facilitar cambios independientes.

## Justificación

- Facturación es más orientada a la emisión y validación documental.
- Cobros está más orientado a movimientos financieros y reconciliación.
- Ambas partes comparten información, pero no deben compartir reglas internas.

## Consecuencias

- Mejor desacoplamiento.
- Evolución más segura de cada negocio.
- Necesidad de contratos claros de integración.
