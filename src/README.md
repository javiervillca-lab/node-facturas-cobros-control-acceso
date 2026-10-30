# Arquitectura del monolito modular

Este directorio concentra la implementación de los distintos contextos de negocio que forman parte del dominio de Nodo.

## Contextos principales

### Facturación

Define la emisión, validación y mantenimiento de facturas, junto con los conceptos y condiciones económicas asociados.

### Cobros

Gestiona la recepción, verificación y aplicación de pagos, así como los excesos y devoluciones.

### Control de acceso

Valida permisos, vigencias, autorizaciones y registros operativos de entrada/salida.

## Principios de diseño

- cada contexto conserva sus propias reglas y estados,
- la comunicación entre módulos se realiza mediante eventos y contratos explícitos,
- la operación financiera no reemplaza a la operación física ni operativa,
- los servicios transversales se mantienen separados del dominio principal.

## Estructura esperada

Cada contexto puede organizarse en capas como:

- `domain/`: agregados, entidades, value objects, eventos y reglas del negocio.
- `application/`: casos de uso y servicios de aplicación.
- `infrastructure/`: persistencia, adaptadores y conectores externos.
- `interfaces/`: controladores, APIs y listeners.

Esta estructura está alineada con la documentación de dominio y con el entregable completo del proyecto.
