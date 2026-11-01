# Estado del proyecto y documentación activa

La documentación del proyecto describe el contexto general del negocio, la arquitectura modular, la delimitación de contextos y el estado de avance del desarrollo.

El sistema se delimita en dos *Bounded Contexts*, ambos *Generic*: Facturación y Cobros y Control de Acceso. Los directorios `src/facturacion/` y `src/cobros/` son módulos internos del primer contexto, no fronteras independientes.

## Estado actual

El repositorio contiene un prototipo con implementación parcial y documentación de diseño. Se añadieron puertos y adaptadores simulados para verificación de pagos, devoluciones, emisión y envío de facturas, avisos previos, derechos de acceso y operaciones de huella. Los adaptadores simulados se identifican en sus resultados y se deshabilitan en producción; no sustituyen las integraciones reales.

También se endurecieron los cálculos monetarios con centavos enteros, la idempotencia de pagos, el plazo de aviso previo a la suspensión y la evaluación de vigencia y horario para el ingreso. Hay pruebas unitarias iniciales para estas reglas, pero todavía no se han ejecutado.

La estructura física no demuestra por sí sola el cumplimiento completo de DDD. Persisten relaciones Doctrine directas entre algunos modelos de módulos distintos, y la autenticación y autorización de operadores todavía no están implementadas.

## Documentación relevante

- [00_ENTREGABLE_COMPLETO.md](./00_ENTREGABLE_COMPLETO.md): visión general del proyecto y modelo de dominio.
- [01-subdominios.md](./01-subdominios.md): división del negocio en subdominios.
- [02-glosario.md](./02-glosario.md): vocabulario básico y lenguaje ubicuo.
- [03-eventstorming.md](./03-eventstorming.md): eventos, comandos y actores.
- [04-domain-stories.md](./04-domain-stories.md): historias de negocio.
- [05-context-map.md](./05-context-map.md): fronteras entre contextos.
- [06-reglas-invariantes.md](./06-reglas-invariantes.md): reglas e invariantes.
- [ESPECIFICACION_FUTURA_PROTOTIPO_Y_PRUEBAS.md](./ESPECIFICACION_FUTURA_PROTOTIPO_Y_PRUEBAS.md): visión técnica futura para interfaces y persistencia.

## Pendientes de implementación y validación

- ejecutar las pruebas unitarias y añadir pruebas de integración para persistencia, idempotencia global y flujos de facturación y acceso;
- validar el contenedor Symfony, los mappings Doctrine y la migración en el motor de base de datos configurado;
- seleccionar e integrar los proveedores reales antes de habilitar operaciones en producción;
- definir autenticación, roles y autorización para operadores; los valores de autorización recibidos desde formularios no acreditan una identidad;
- reducir las dependencias de persistencia cruzadas y documentar los contratos de integración entre los dos contextos;
- confirmar los horarios, vigencias y políticas de acceso con la fuente real de derechos.
