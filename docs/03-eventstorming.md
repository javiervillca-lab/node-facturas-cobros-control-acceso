# 03. EventStorming

## Objetivo y fronteras

Este resumen organiza eventos y comandos por los dos *Bounded Contexts* del [modelo de dominio de Nodo](./00_ENTREGABLE_COMPLETO.md). Los nombres son lenguaje de dominio, no implican que todos los eventos o integraciones ya estén implementados.

## Facturación y Cobros — *Generic*

### Comandos

- Liquidar conceptos de cobro y crear la cuenta.
- Informar y verificar el pago.
- `RegistrarPagoConfirmado` para aplicar un movimiento verificado.
- Autorizar y confirmar la devolución de un exceso.
- Preparar la factura, adquirir su emisión y confirmar el resultado verificado.
- Enviar la factura y registrar la constancia de envío.

### Eventos

- `CuentaPorCobrarCreada`
- `PagoInformadoRegistrado`
- `PagoVerificadoRegistrado`
- `PagoRegistrado`
- `ExcesoPagoIdentificado`
- `CuentaSaldada`
- `DevolucionConfirmada` cuando el resultado de salida está verificado
- `FacturaPreparada`
- `FacturaEmitida`
- `FacturaEnviada`

Un comprobante informado por el cliente no produce `PagoVerificadoRegistrado` ni reduce el saldo. Los eventos describen hechos confirmados. Un exceso se conserva y tramita por separado; no convierte el saldo en negativo ni reabre una cuenta saldada. Los conceptos forman parte de la creación de la cuenta y no se agregan después como un evento separado.

## Control de Acceso — *Generic*

### Comandos

- `EvaluarIngreso`
- `SolicitarSuspension`
- `SolicitarRestablecimiento`
- Adquirir y completar la operación de registro de huella.
- Registrar la evidencia de resultado o incertidumbre.

### Eventos

- `SuspensionSolicitada`
- `AccesoSuspendido`
- `RestablecimientoSolicitado`
- `AccesoRestablecido`

`EvaluacionAcceso` es un valor de dominio que contiene el resultado para suspender, restablecer o ingresar; no es un evento de dominio. Las decisiones de ingreso y las operaciones auxiliares se registran según sus contratos y auditoría, sin inventar eventos de éxito. Una operación solicitada o adquirida no acredita que el efecto físico haya ocurrido. El contexto conserva el resultado real y trata explícitamente la incertidumbre.

## Políticas y actores

- Recepción puede actuar con funciones diferenciadas para administración, verificación, emisión y operación de huella.
- Dirección autoriza excepciones definidas en el modelo, como devoluciones de exceso.
- Los subdominios de Membresías, Reservas, Agenda y Tarifas son fuentes de derechos o datos valorizados, no contextos financieros adicionales.
- Control de Acceso consume la situación financiera mediante un contrato traducido; el Context Map de `05-context-map.md` detalla la integración.

Este resumen debe validarse en un EventStorming colaborativo antes de considerarse especificación exhaustiva de los procesos.
