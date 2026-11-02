# Módulo financiero de cuentas y cobros

Este módulo pertenece al único contexto Facturación y Cobros junto con su módulo financiero vecino.

| Capa | Elementos presentes |
|---|---|
| domain | CuentaPorCobrar, ConceptoCobro, AplicacionPago, DevolucionExceso y Dinero |
| application | LiquidarCuentaService, RegistrarPagoService y GestionarDevolucionService |
| infrastructure | CuentaPorCobrarRepository, SituacionFinancieraQuery y simuladores de pago y devolución |
| interfaces | Controlador web y formularios Twig correspondientes |

Consulta el [modelo de dominio](../../docs/00_ENTREGABLE_COMPLETO.md) para las reglas, los diagramas y los contratos. Los adaptadores externos actuales son simulados; la persistencia utiliza entidades mapeadas con Doctrine.
