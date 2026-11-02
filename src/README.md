# Mapa de la implementación

La solución organiza dos contextos y tres raíces; consulta el [documento completo](../docs/00_ENTREGABLE_COMPLETO.md).

| Directorio | Contexto | Contenido |
|---|---|---|
| [cobros](cobros/README.md) | Facturación y Cobros | CuentaPorCobrar, pagos, excesos y devolución |
| [facturacion](facturacion/README.md) | Facturación y Cobros | Factura, líneas, emisión y envío |
| [control_acceso](control_acceso/README.md) | Control de Acceso | AccesoMiembro, derecho, aviso y operación de huella |
| Entity y Repository | Soporte de la aplicación | Cliente y su persistencia |
| Command | Entrada por consola | Preparación de escenario y datos de ejemplo |
| Migrations | Persistencia | Migraciones Doctrine |

Cada módulo usa `domain/`, `application/`, `infrastructure/` e `interfaces/`. Los controladores delegan casos de uso, los servicios consultan puertos y los adaptadores concretan persistencia y simulación. `config/services.yaml` enlaza interfaces e implementaciones.

La consulta financiera pública es `cobros/infrastructure/SituacionFinancieraQuery.php`; el adaptador consumidor `control_acceso/infrastructure/SituacionFinancieraAdapter.php` traduce el resultado. Acceso no importa el agregado CuentaPorCobrar para decidir. Las responsabilidades y contratos se detallan en los capítulos 6–10 del documento principal.
