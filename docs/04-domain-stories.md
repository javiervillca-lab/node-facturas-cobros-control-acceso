# 04. Domain Stories

## Como es hoy

- Un operador registra una membresía o una reserva para un cliente.
- El cliente recibe una factura por el servicio contratado.
- El pago puede registrarse manualmente o por integración.
- El responsable revisa accesos, pendientes y pagos vencidos.

## Como debería ser

- El sistema identifica automáticamente qué factura corresponde a la membresía, reserva o evento.
- La confirmación del pago actualiza el estado del servicio y/o acceso.
- Las alertas de vencimiento y acceso no autorizado se disparan según reglas definidas.
- Los reportes de ingresos, reservas y accesos se actualizan en tiempo real.

## Historias de dominio sugeridas

### Historia 1
Como operador de coworking, quiero emitir una factura con los servicios contratados para registrar correctamente la obligación del cliente.

### Historia 2
Como cliente, quiero pagar mi membresía o reserva desde un canal seguro para activar mi acceso o servicio.

### Historia 3
Como encargado de control de acceso, quiero validar que el usuario tenga un pase vigente antes de permitir su ingreso.

### Historia 4
Como responsable de cobros, quiero identificar facturas vencidas para priorizar la gestión de pagos y la regularización.
