# Contexto: Cobros

## Propósito

Este contexto gestiona la recepción, verificación y aplicación de los pagos asociados a deudas o facturas. Su objetivo es convertir un movimiento financiero en un registro confiable y consistente con la obligación comercial correspondiente.

## Responsabilidades

- registrar pagos informados por terceros o por procesos internos,
- verificar la referencia, el movimiento y la identidad del origen,
- aplicar un pago a la deuda o factura correcta,
- identificar excesos y separar devoluciones o compensaciones,
- mantener el estado de la cuenta por cobrar,
- regularizar discrepancias y evitar doble aplicación.

## Conceptos clave

- pago informado,
- verificación de movimiento,
- aplicación de pago,
- exceso,
- devolución,
- cuenta por cobrar,
- saldo pendiente.

## Invariantes principales

- todo pago debe corresponder a una factura, servicio o deuda válida,
- la suma aplicada no puede superar el monto pendiente sin registrar exceso,
- una aplicación no puede perder la trazabilidad del movimiento original,
- el exceso y la devolución deben gestionarse como estados explícitos y no como saldos ocultos.

## Eventos de dominio sugeridos

- `PagoInformado`
- `PagoVerificado`
- `PagoAplicado`
- `ExcesoDetectado`
- `DevolucionSolicitada`
- `DevolucionEjecutada`

## Capas sugeridas

- `domain/`: entidades, value objects, agregados de pago y cuenta por cobrar.
- `application/`: servicios de conciliación, aplicación y devolución.
- `infrastructure/`: integraciones bancarias, almacenamiento y notificaciones.
- `interfaces/`: endpoints de registro de pago y consulta de situación.

## Estado actual

El contexto está formalizado como una frontera de negocio separada y con contratos claros. La implementación funcional deberá completarse con los casos de uso de aplicación y la persistencia del flujo financiero.
