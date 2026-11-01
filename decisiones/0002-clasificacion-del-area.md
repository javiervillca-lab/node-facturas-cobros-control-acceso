# 0002. Clasificación de subdominios y contextos

## Contexto

La clasificación orienta la estrategia de desarrollo, pero no sustituye la definición de límites de contexto. El mapa de Nodo conserva ocho subdominios y sus categorías estratégicas.

## Decisión

La solución implementa dos *Bounded Contexts*, ambos *Generic*:

- **Facturación y Cobros**: liquidación, obligaciones, conciliación y aplicación de pagos, excesos, devoluciones y emisión de facturas.
- **Control de Acceso**: evaluación del ingreso, habilitación del miembro y coordinación de operaciones confirmadas sobre la huella.

Reservas y disponibilidad se clasifica como *Core*. Membresías y planes, Agenda de eventos, Tarifas y precios y Espacios y recursos se clasifican como *Supporting*. Notificaciones es *Generic* transversal. Facturación y Cobros es un solo subdominio *Generic*, no dos subdominios principales.

## Consecuencias

- La organización interna puede separar facturación y cobros por responsabilidades, sin crear una frontera DDD entre ellos.
- El valor estratégico *Core* del mapa corresponde a Reservas y disponibilidad; no se atribuye a Facturación, Cobros o Control de Acceso.
- La clasificación y las fronteras se mantienen consistentes entre ADR, Context Map, glosario y documentación del código.
