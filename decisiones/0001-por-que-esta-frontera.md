# 0001. Frontera entre los dos contextos

## Contexto

Una versión anterior de esta decisión separaba Facturación y Cobros en contextos distintos. Esa frontera queda reemplazada por la decisión descrita aquí.

## Decisión

El sistema desarrolla dos *Bounded Contexts*: **Facturación y Cobros** y **Control de Acceso**. Ambos se clasifican como *Generic*. Facturación y Cobros es un único contexto financiero que protege obligaciones, pagos, excesos, devoluciones y facturas. Control de Acceso protege la elegibilidad y las operaciones sobre el registro de huella.

## Justificación

- La conciliación determina qué importe se aplicó a una obligación y qué conceptos documenta la factura.
- `CuentaPorCobrar` y `Factura` tienen invariantes y ciclos de vida distintos, pero pertenecen al mismo lenguaje financiero.
- Separar responsabilidades internas no convierte automáticamente cada módulo o carpeta en un *Bounded Context*.
- Control de Acceso necesita consultar una situación financiera, pero no debe compartir ni modificar los agregados financieros.

## Consecuencias

- `src/facturacion/` y `src/cobros/` son áreas internas del contexto Facturación y Cobros, no contextos autónomos.
- `CuentaPorCobrar` y `Factura` mantienen consistencia mediante sus reglas y contratos dentro del contexto financiero.
- Control de Acceso consulta información financiera mediante un contrato público y una ACL del lado consumidor.
- Cambios futuros en la frontera requieren actualizar este ADR y el Context Map de forma coordinada.
