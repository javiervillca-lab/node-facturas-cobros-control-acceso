# Módulo interno: Facturación

Este directorio contiene responsabilidades de facturación dentro del Bounded Context **Facturación y Cobros**, clasificado como *Generic*. No es un contexto delimitado independiente de `src/cobros/`.

## Responsabilidad

Representar el documento que refleja los conceptos efectivamente cobrados, preparar y seguir su emisión, y conservar su relación con la cuenta y el servicio de origen. La factura mantiene un ciclo de vida documental propio: su estado no sustituye ni duplica el saldo y el estado de `CuentaPorCobrar`.

## Lenguaje del contexto

`Factura`, `LineaFactura`, `TipoDocumento`, `PendienteDeEmision`, `Emitida` y `Enviada` describen el documento. `CuentaPorCobrar`, `AplicacionPago` y `Saldada` describen la obligación y el cobro dentro del mismo contexto financiero; no deben interpretarse como términos de un contexto separado.

## Límite e integración

La raíz `Factura` conserva sus propias invariantes y se relaciona con `CuentaPorCobrar` mediante identidad y datos explícitos, sin convertir ambas raíces en una sola entidad. La emisión fiscal es un efecto externo y su resultado debe confirmarse antes de declarar la factura como emitida.

## Estructura

- `domain/`: agregados, valores y políticas de acceso.
- `application/`: evaluación y coordinación de operaciones.
- `infrastructure/`: persistencia, ACL y adaptadores de dispositivos.
- `interfaces/`: entradas administrativas y APIs.

Las reglas, estados y relaciones del dominio se describen en la documentación del proyecto.
