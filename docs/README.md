# Documentación de Nodo

El [documento principal completo](00_ENTREGABLE_COMPLETO.md) reúne el diseño estratégico y táctico. Es la referencia para nombres, reglas, estados y diagramas; las consultas temáticas siguientes permiten acceder a cada parte del modelo.

## Para ejecutar y probar

- [Instalación con Docker Compose](INSTALACION_Y_EJECUCION_DOCKER.md).
- [Comandos y pruebas del proyecto](../README.md#pruebas).

## Consultas por tema

1. [Subdominios y fronteras](01-subdominios.md).
2. [Lenguaje ubicuo](02-glosario.md).
3. [EventStorming](03-eventstorming.md).
4. [Actores e historias de dominio](04-domain-stories.md).
5. [Context Map y contratos](05-context-map.md).
6. [Invariantes I01–I16](06-reglas-invariantes.md).

Estas consultas reproducen las secciones correspondientes del documento principal. Al cambiar una regla, revisar también la consulta temática; no mantener dos definiciones diferentes.

Las [decisiones arquitectónicas](../decisiones/0001-por-que-esta-frontera.md) explican las fronteras; el [mapa del código](../src/README.md) permite localizar su implementación. Los contratos del [capítulo 6](00_ENTREGABLE_COMPLETO.md#capitulo-06) describen la integración del modelo.
