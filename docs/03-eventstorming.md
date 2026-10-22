# 03. EventStorming

## Objetivo

Mapear eventos del negocio, comandos, actores y políticas para comprender el flujo de la operación de coworking y eventos.

## Eventos sugeridos

- `MembresiaActivada`
- `FacturaEmitida`
- `PagoRegistrado`
- `PagoRechazado`
- `FacturaVencida`
- `ReservaConfirmada`
- `AccesoAutorizado`
- `AccesoDenegado`
- `EventoProgramado`

## Comandos sugeridos

- `RegistrarMembresia`
- `EmitirFactura`
- `RegistrarPago`
- `ReconciliarCobro`
- `CrearReserva`
- `AutorizarAcceso`
- `CancelarReserva`
- `NotificarVencimiento`

## Agentes / actores

- Cliente
- Operador de coworking
- Encargado de eventos
- Responsable de cobros
- Sistema de control de acceso
- Sistema de notificaciones

## Observaciones

Este documento debe complementarse con el muro de EventStorming (o export del mismo) que defina con mayor detalle los flujos de acceso y pago.
