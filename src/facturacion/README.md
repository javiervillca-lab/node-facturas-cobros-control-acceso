# Contexto: Facturación

## Propósito

Este contexto transforma los servicios contratados en obligaciones económicas consistentes, verificables y facturables. Su responsabilidad principal es la emisión, mantenimiento y validación del ciclo de vida de la factura.

## Responsabilidades

- gestionar clientes, conceptos de cargo y cuentas por cobrar asociadas,
- validar datos del cliente, del servicio contratado y de los importes,
- calcular descuentos, recargos, impuestos y totales,
- crear y mantener estados de la factura,
- preparar la factura para su emisión y entrega,
- conservar la relación entre el servicio contratado y la obligación económica generada.

## Conceptos clave

- Cliente
- Cuenta o obligación
- Concepto de cargo
- Línea de factura
- Factura
- Estado de factura
- Descuento y recargo

## Invariantes principales

- el total de la factura debe coincidir con la suma de los conceptos facturados,
- una factura no puede quedar simultáneamente pagada y vencida por el mismo concepto,
- los importes comprometidos no deben modificarse retroactivamente por cambios de tarifa,
- la factura debe conservar la identidad del servicio o contrato que la originó.

## Eventos de dominio sugeridos

- `FacturaGenerada`
- `FacturaEmitida`
- `FacturaVencida`
- `FacturaAnulada`
- `ConceptoFacturado`

## Capas sugeridas

- `domain/`: entidades, value objects, agregados y reglas del dominio.
- `application/`: servicios de liquidación, validación y preparación de emisión.
- `infrastructure/`: persistencia, adaptadores fiscales y servicios externos.
- `interfaces/`: APIs, controladores y event listeners.

## Estado actual

El contexto está definido como base arquitectónica y de dominio, con responsabilidades y reglas claras. La implementación concreta de agregados y casos de uso queda pendiente para la siguiente fase de desarrollo.
