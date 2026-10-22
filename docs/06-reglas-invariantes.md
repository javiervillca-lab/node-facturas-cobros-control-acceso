# 06. Reglas e Invariantes

## Reglas del dominio

- Una factura no puede estar en estado `pagada` y `vencida` al mismo tiempo.
- Un cobro solo puede registrarse si la factura o servicio asociado existe y está vigente.
- El importe total de la factura debe ser la suma de los conceptos, reservas o servicios incluidos.
- Un pago no puede exceder el total pendiente del servicio o evento.
- Un usuario solo puede acceder si posee una membresía, reserva o pase válido.
- Un acceso denegado debe quedar registrado para auditoría.

## Invariantes sugeridas

### Facturación
- El total de la factura debe ser mayor que cero.
- El cliente debe estar registrado y ser válido.
- Los conceptos y servicios facturados deben estar activos.

### Cobros
- El monto pagado debe ser > 0.
- Debe existir un registro de factura o servicio asociado.
- El estado del cobro debe ser consistente con la transacción y la deuda.

### Control de acceso
- La membresía o reserva debe estar activa al momento del ingreso.
- Un evento o espacio solo puede autorizarse cuando corresponde al usuario o pase validado.
- El registro de acceso debe asociarse a identidad, horario y motivo de ingreso.

## Validación

Estas reglas deben aplicarse en el dominio y reforzarse también en la capa de infraestructura cuando corresponda.
