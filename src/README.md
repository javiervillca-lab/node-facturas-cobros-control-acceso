# Arquitectura del monolito modular

Este directorio contiene la implementación de los dos *Bounded Contexts* del sistema. Ambos se clasifican como *Generic*.

## Contextos delimitados

### Facturación y Cobros

Un único contexto financiero que protege las obligaciones, los pagos y su conciliación, los excesos y devoluciones y la emisión de facturas. `facturacion/` y `cobros/` son módulos internos de esta frontera, no contextos independientes. `CuentaPorCobrar` y `Factura` son agregados distintos dentro del mismo contexto.

### Control de Acceso

Contexto que protege la elegibilidad del ingreso, la habilitación del miembro y las operaciones sobre el registro de huella. Consulta la situación financiera mediante un contrato traducido y no accede directamente a entidades ni repositorios financieros.

## Principios de diseño

- cada contexto protege su modelo, lenguaje e invariantes,
- las integraciones entre contextos usan contratos explícitos y no comparten agregados mutables,
- la separación física de carpetas no define por sí sola una frontera de contexto,
- los hechos financieros y las actuaciones físicas se registran y confirman por separado.

La estructura de capas concreta depende de cada módulo; la arquitectura objetivo, los agregados y los contratos se describen en la documentación de dominio del proyecto.

## Estructura esperada

Cada contexto puede organizarse en capas como:

- `domain/`: agregados, entidades, value objects, eventos y reglas del negocio.
- `application/`: casos de uso y servicios de aplicación.
- `infrastructure/`: persistencia, adaptadores y conectores externos.
- `interfaces/`: controladores, APIs y listeners.

Esta estructura representa una organización posible de responsabilidades y no implica que todas las capas o capacidades estén implementadas.
