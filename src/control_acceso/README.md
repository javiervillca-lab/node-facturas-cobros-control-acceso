# Contexto Control de Acceso

Este contexto consulta la situación económica mediante su puerto y traduce el contrato público a SituacionPago.

| Capa | Elementos presentes |
|---|---|
| domain | AccesoMiembro, OperacionRegistroHuella, AvisoPrevio, SituacionPago, DerechoAcceso y EvaluadorDeIngreso |
| application | SuspenderAccesoService, RestablecerAccesoService y EvaluarIngresoService |
| infrastructure | Repositorios, traducción financiera, consulta de perfil y simuladores de aviso, derecho y huella |
| interfaces | Controlador web y formularios Twig correspondientes |

Consulta el [modelo de dominio](../../docs/00_ENTREGABLE_COMPLETO.md) para las reglas, los diagramas y los contratos. Los adaptadores externos actuales son simulados; la persistencia utiliza entidades mapeadas con Doctrine.
