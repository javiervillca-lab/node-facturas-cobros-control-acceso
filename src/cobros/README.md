# Módulo interno: Cobros

Este directorio contiene responsabilidades de cobro dentro del Bounded Context **Facturación y Cobros**, clasificado como *Generic*. No es un contexto delimitado independiente de `src/facturacion/`.

## Responsabilidad

Registrar y verificar movimientos de pago, aplicarlos a las obligaciones, conservar su trazabilidad y gestionar excesos y devoluciones. El saldo pertenece a `CuentaPorCobrar`; un pago recibido que excede lo pendiente se registra como exceso y no produce un saldo negativo ni se pierde.

## Lenguaje del contexto

`PagoInformado` representa la comunicación recibida; no confirma que la empresa haya verificado el movimiento. `AplicacionPago` conserva el importe recibido, aplicado y el exceso asociado a la referencia original. `Pendiente` y `Saldada` describen el estado de la cuenta, no el estado documental de `Factura`.

## Límite e integración

La conciliación y aplicación de pagos pertenecen al mismo contexto que la emisión de facturas, pero `CuentaPorCobrar` y `Factura` son agregados con invariantes propias. La situación financiera se expone a Control de Acceso mediante un contrato público; el contexto consumidor no consulta este módulo directamente.

## Estructura

- `domain/`: agregados, valores y políticas de acceso.
- `application/`: evaluación y coordinación de operaciones.
- `infrastructure/`: persistencia, ACL y adaptadores de dispositivos.
- `interfaces/`: entradas administrativas y APIs.

Las reglas, estados y relaciones del dominio se describen en la documentación del proyecto.
