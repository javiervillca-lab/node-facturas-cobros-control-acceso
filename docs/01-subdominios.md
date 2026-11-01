# 01. Subdominios y contextos delimitados

Nodo conserva ocho subdominios; la solución desarrolla dos *Bounded Contexts*: **Facturación y Cobros**, y **Control de Acceso**. Ambos se clasifican como *Generic*. Facturación y Cobros no son dos contextos ni dos subdominios separados en este modelo.

## Subdominios de Nodo

| Subdominio | Clasificación | Responsabilidad |
|---|---|---|
| Reservas y disponibilidad | *Core* | Evitar solapes y gestionar disponibilidad, reservas, consumos y cancelaciones |
| Membresías y planes | *Supporting* | Definir paquetes, derechos, cuota, pagador, beneficiarios y vigencia |
| Agenda de eventos | *Supporting* | Gestionar eventos, depósitos requeridos, servicios extra y derechos temporales |
| Tarifas y precios | *Supporting* | Proveer cotizaciones, descuentos y precios versionados |
| Espacios y recursos | *Supporting* | Mantener recursos, capacidades y características operativas |
| Facturación y Cobros | *Generic* | Liquidar cargos, conciliar pagos, gestionar excesos y devoluciones y emitir facturas |
| Control de Acceso | *Generic* | Evaluar derechos de ingreso y coordinar operaciones confirmadas sobre la huella |
| Notificaciones | *Generic* transversal | Entregar mensajes y devolver resultados correlacionados |

## Contextos delimitados de esta solución

### Facturación y Cobros — *Generic*

Un único contexto financiero que comprende los agregados `CuentaPorCobrar` y `Factura`. Protege conceptos, obligaciones, pagos, aplicaciones, excesos, devoluciones y documentos emitidos. Sus carpetas `facturacion/` y `cobros/` organizan responsabilidades internas del mismo contexto y no deben interpretarse como fronteras de contexto distintas.

### Control de Acceso — *Generic*

Contexto separado que protege la elegibilidad de ingreso, el permiso del miembro y las operaciones sobre el registro de huella. Consulta la situación económica del contexto Facturación y Cobros mediante un contrato traducido; no comparte su modelo financiero.

## Relaciones

- Membresías, Reservas, Agenda de eventos y Tarifas entregan a Facturación y Cobros hechos y conceptos valorizados.
- Facturación y Cobros expone la situación económica que Control de Acceso requiere para evaluar sus propias reglas.
- Los dos contextos conservan sus modelos e invariantes. Los contratos de integración no comparten entidades mutables.
