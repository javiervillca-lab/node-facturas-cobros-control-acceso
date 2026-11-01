# Nodo — facturación, cobros y control de acceso

Nodo es un monolito modular para la operación de coworking, espacios, reservas, eventos y servicios asociados. La solución se organiza en **dos Bounded Contexts, Facturación y Cobros y Control de Acceso, ambos Generic**.

## Contextos delimitados

### Facturación y Cobros — *Generic*

Un único contexto financiero que transforma servicios valorizados en obligaciones, verifica y aplica pagos, identifica excesos y gestiona devoluciones y emite facturas. Los directorios `src/facturacion/` y `src/cobros/` son módulos internos de este contexto, no Bounded Contexts separados. `CuentaPorCobrar` y `Factura` son agregados distintos dentro de esta misma frontera.

### Control de Acceso — *Generic*

Evalúa el derecho de ingreso, administra la habilitación del miembro y coordina operaciones confirmadas sobre el registro de huella. Consulta la situación económica del contexto financiero mediante un contrato y una ACL, sin acceder a sus agregados o repositorios.

## Estructura relevante

```text
docs/                 Documentación del dominio y del diseño objetivo
decisiones/           Decisiones arquitectónicas (ADR)
src/
  facturacion/        Módulo interno de Facturación y Cobros
  cobros/             Módulo interno de Facturación y Cobros
  control_acceso/     Contexto Control de Acceso
  Controller/         Entrada web de la aplicación
  Entity/             Persistencia de la aplicación
  Repository/         Repositorios de la aplicación
  Migrations/         Migraciones Doctrine
```

La estructura física del código describe módulos y adaptadores; no modifica los límites del modelo de dominio. Reservas y disponibilidad se clasifica como *Core* dentro del mapa de subdominios de Nodo, aunque no constituye uno de los contextos implementados como foco de este proyecto.

## Estado

El repositorio contiene un prototipo y documentación de diseño. El alcance futuro, las capacidades pendientes y las diferencias entre el objetivo y la implementación actual se detallan en [el estado pendiente](docs/README_ESTADO_PENDIENTE.md). No se debe interpretar el diseño documentado como funcionalidad ya implementada.

## Documentación

- [Modelo de dominio y entregable completo](docs/00_ENTREGABLE_COMPLETO.md)
- [Subdominios y contextos delimitados](docs/01-subdominios.md)
- [Glosario](docs/02-glosario.md)
- [EventStorming](docs/03-eventstorming.md)
- [Historias de dominio](docs/04-domain-stories.md)
- [Context Map](docs/05-context-map.md)
- [Reglas e invariantes](docs/06-reglas-invariantes.md)
- [Estado y pendientes](docs/README_ESTADO_PENDIENTE.md)
- [Especificación técnica futura](docs/ESPECIFICACION_FUTURA_PROTOTIPO_Y_PRUEBAS.md)

## Próximos pasos

1. Contrastar la implementación existente con los agregados, invariantes y casos de uso documentados.
2. Completar los contratos públicos entre Facturación y Cobros y Control de Acceso.
3. Implementar pruebas de dominio e integración para los invariantes documentados.
