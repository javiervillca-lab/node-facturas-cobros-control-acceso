# 05. Context Map

## Propósito

Definir las fronteras entre contextos de negocio y sus relaciones en un sistema de coworking y eventos.

## Contextos

### Facturación
- Responsable de emitir facturas por membresías, eventos y reservas.
- Define conceptos, descuentos, impuestos y estados financieros.

### Cobros
- Responsable de registrar pagos y reconciliar movimientos financieros.
- Depende de la información emitida por facturación para validar deudas y pagos.

### Control de acceso
- Responsable de autorizar entradas, validar pases y registrar accesos.
- Se integra con reservas, eventos y membresías para verificar permisos.

## Relación sugerida

- `Facturación` -> `Cobros`: intercambio de facturas, importes y estados de pago.
- `Facturación` -> `Control de acceso`: información de membresía, reserva o evento asociado.
- `Cobros` -> `Control de acceso`: validación de pagos pendientes o pagos confirmados antes del acceso.

## Tipo de integración

- APIs REST o eventos de dominio.
- Contratos explícitos y eventos de negocio, como `FacturaEmitida`, `PagoRegistrado` o `AccesoAutorizado`.

## Recomendación

Mantener una frontera clara para evitar que el contexto de control de acceso conozca reglas internas de facturación o cobros que no correspondan a su responsabilidad.
