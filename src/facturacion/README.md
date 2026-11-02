# Módulo financiero de factura

Este módulo pertenece al único contexto Facturación y Cobros junto con su módulo financiero vecino.

| Capa | Elementos presentes |
|---|---|
| domain | Factura, LineaFactura y resultados de emisión y envío |
| application | EmitirFacturaService y EnviarFacturaService |
| infrastructure | FacturaRepository y simuladores de emisión fiscal y entrega |
| interfaces | Controlador web y formularios Twig correspondientes |

Consulta el [modelo de dominio](../../docs/00_ENTREGABLE_COMPLETO.md) para las reglas, los diagramas y los contratos. Los adaptadores externos actuales son simulados; la persistencia utiliza entidades mapeadas con Doctrine.
