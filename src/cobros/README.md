# Contexto: Cobros

## Propósito

Gestionar la recepción, conciliación y seguimiento de pagos asociados a facturas.

## Responsabilidades

- Registrar pagos.
- Validar que exista una factura asociada.
- Actualizar el estado de la deuda o factura.
- Gestionar reconciliación y reportes de cobro.

## Capas sugeridas

- `domain/`: entidades, value objects, agregados, reglas.
- `application/`: casos de uso y servicios de aplicación.
- `infrastructure/`: persistencia, integraciones bancarias y notificaciones.
- `interfaces/`: endpoints, eventos, listeners.

## TODO

- Definir modelos de pago y conciliación.
- Establecer políticas de validación.
- Identificar eventos del flujo de cobro.
- Definir contratos de integración con facturación.
