# Nodo de coworking y eventos

Este repositorio está estructurado como un esqueleto base de Domain-Driven Design (DDD) para un sistema de coworking con gestión de eventos, facturación, cobros y control de acceso.

## Objetivo

Definir una base arquitectónica para separar los contextos de negocio, documentar el lenguaje ubicuo, los eventos y las decisiones de diseño del dominio.

## Estructura

- `docs/`: documentación del dominio, eventos, historias y reglas.
- `decisiones/`: decisiones arquitectónicas y de frontera de contexto.
- `src/`: implementación de los contextos del dominio.

## Contextos principales

- `src/facturacion/`: facturación de membresías, reservas y eventos.
- `src/cobros/`: cobros de pagos, cuotas, reservas y regularización.
- `src/control-acceso/`: control de ingreso, acreditación y permisos de acceso.

## Siguientes pasos

1. Definir el lenguaje ubicuo del negocio.
2. Validar los subdominios y sus fronteras.
3. Identificar agregados, entidades y value objects.
4. Continuar con la implementación de cada contexto.
5. Vincular la integración mediante eventos o APIs.
