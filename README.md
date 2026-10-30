# Nodo — proyecto de coworking, facturación, cobros y control de acceso

Este repositorio se organiza como un monolito modular siguiendo principios de Domain-Driven Design (DDD). La arquitectura separa responsabilidades por contexto de negocio para evitar mezclar reglas financieras, operativas y de autorización.

## Propósito del sistema

Nodo combina la operación de coworking con la gestión de espacios, reservas, eventos y servicios adicionales. El sistema debe soportar:

- membresías y accesos de clientes,
- alquileres de salas y escritorios,
- reservas y eventos,
- facturación por servicios y consumos,
- conciliación y aplicación de pagos,
- control de acceso y auditoría operativa.

## Contextos del dominio

### Facturación

Responsable de convertir servicios y contratos en obligaciones económicas claras y verificables. Centraliza la emisión de facturas, cálculo de importes, validación de conceptos y mantenimiento del estado documental.

### Cobros

Responsable de registrar, verificar y conciliar pagos. Gestiona la aplicación de abonos, excesos, saldos y devoluciones, sin mezclar estas reglas con la emisión de facturas.

### Control de acceso

Responsable de autorizar o denegar accesos, validar permisos, vigencias y condiciones de ingreso, así como mantener registros de huella y eventos de acceso.

## Estructura del repositorio

```text
node-facturas-cobros-control-acceso/
├── docs/                          # documentación de dominio y entregables
├── decisiones/                    # ADRs y decisiones de frontera
├── src/
│   ├── facturacion/               # contexto de facturación
│   ├── cobros/                    # contexto de cobros
│   ├── control-acceso/            # contexto de control de acceso
│   ├── Controller/                # controladores Symfony
│   ├── Entity/                    # entidades persistentes base
│   ├── Repository/                # repositorios
│   ├── Migrations/                # migraciones Doctrine
│   └── Kernel.php                 # arranque de la aplicación
├── config/
├── public/
├── templates/
├── tests/
├── composer.json
├── symfony.lock
├── Makefile
├── docker-compose.yml
├── README.md
├── docs/00_ENTREGABLE_COMPLETO.md
└── docs/README_ESTADO_PENDIENTE.md
```

## Reglas de diseño aplicadas

- separación clara por bounded contexts,
- lenguaje ubicuo compartido en toda la documentación,
- reglas de negocio centradas en el dominio,
- contratos explícitos entre módulos,
- coordinación mediante eventos y comandos de aplicación,
- mantenimiento de autonomía entre realidad financiera y realidad operativa.

## Estado actual

El repositorio se encuentra en una etapa de definición arquitectónica y de dominio. La estructura base y la documentación ya están establecidas; la implementación real de entidades, agregados, servicios y persistencia está pendiente para cada contexto.

## Documentación principal

- `docs/00_ENTREGABLE_COMPLETO.md`: entregable principal con el contexto general del proyecto.
- `docs/01-subdominios.md`: clasificación de subdominios.
- `docs/02-glosario.md`: términos y lenguaje ubicuo.
- `docs/03-eventstorming.md`: eventos, comandos y actores.
- `docs/04-domain-stories.md`: historias de dominio.
- `docs/05-context-map.md`: fronteras entre contextos.
- `docs/06-reglas-invariantes.md`: reglas e invariantes del sistema.

## Siguientes pasos recomendados

1. definir agregados, entidades y value objects por contexto,
2. formalizar repositorios y puertos de infraestructura,
3. establecer casos de uso de aplicación,
4. crear contratos de integración entre facturación, cobros y acceso,
5. preparar pruebas de dominio e integración sobre los invariantes clave.
