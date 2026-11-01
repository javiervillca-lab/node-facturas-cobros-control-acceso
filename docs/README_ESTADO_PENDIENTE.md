# Estado del proyecto y documentación activa

La documentación del proyecto describe el contexto general del negocio, la arquitectura modular, la delimitación de contextos y el estado de avance del desarrollo.

El sistema se delimita en dos *Bounded Contexts*, ambos *Generic*: Facturación y Cobros y Control de Acceso. Los directorios `src/facturacion/` y `src/cobros/` son módulos internos del primer contexto, no fronteras independientes.

## Estado actual

El repositorio contiene un prototipo con implementación parcial y documentación de diseño objetivo. La estructura física no demuestra por sí sola el cumplimiento de DDD: se debe contrastar cada flujo implementado con los agregados, invariantes y contratos del modelo antes de declararlo completo.

## Documentación relevante

- [00_ENTREGABLE_COMPLETO.md](./00_ENTREGABLE_COMPLETO.md): visión general del proyecto y modelo de dominio.
- [01-subdominios.md](./01-subdominios.md): división del negocio en subdominios.
- [02-glosario.md](./02-glosario.md): vocabulario básico y lenguaje ubicuo.
- [03-eventstorming.md](./03-eventstorming.md): eventos, comandos y actores.
- [04-domain-stories.md](./04-domain-stories.md): historias de negocio.
- [05-context-map.md](./05-context-map.md): fronteras entre contextos.
- [06-reglas-invariantes.md](./06-reglas-invariantes.md): reglas e invariantes.
- [ESPECIFICACION_FUTURA_PROTOTIPO_Y_PRUEBAS.md](./ESPECIFICACION_FUTURA_PROTOTIPO_Y_PRUEBAS.md): visión técnica futura para interfaces y persistencia.

## Objetivo de la etapa actual

- mantener la separación de responsabilidades por contexto,
- continuar con la definición de agregados, entidades y value objects,
- preparar los repositorios y puertos de infraestructura,
- establecer el contrato de integración entre Facturación y Cobros y Control de Acceso,
- contrastar el prototipo existente con las reglas y el diseño objetivo antes de declarar funcionalidades completas.
