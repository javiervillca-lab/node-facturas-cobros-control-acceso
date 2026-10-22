# Contexto: Facturación

## Propósito

Gestionar la emisión, validación y mantenimiento del ciclo de vida de las facturas.

## Responsabilidades

- Crear y emitir facturas.
- Validar datos del cliente y conceptos.
- Calcular importes.
- Gestionar estados de factura.

## Capas sugeridas

- `domain/`: entidades, value objects, agregados, reglas.
- `application/`: casos de uso y servicios de aplicación.
- `infrastructure/`: persistencia, clientes externos, integraciones.
- `interfaces/`: controladores, APIs, eventos de entrada/salida.

## TODO

- Definir Aggregates y Entities.
- Identificar Value Objects.
- Especificar repositorios.
- Definir eventos de dominio.
