# 02. Glosario y lenguaje ubicuo

Los términos se interpretan dentro de uno de los dos contextos delimitados en el modelo de Nodo. Si una palabra cambia de significado entre contextos, se debe explicitar el contexto o usar el término del contrato publicado.

## Facturación y Cobros — *Generic*

| Término | Significado acordado |
|---|---|
| CuentaPorCobrar | Obligación económica que reúne conceptos de un pagador, moneda y período o tramo, con saldo y vencimiento propios |
| ConceptoCobro | Servicio o importe valorizado incorporado a una cuenta, con origen y precio comprometido identificables |
| AplicacionPago | Registro trazable de un movimiento verificado, con importe recibido, aplicado y posible exceso |
| PagoInformado | Comunicación de un pago por un cliente o tercero; no equivale a verificación ni aplicación |
| Exceso | Parte verificada del movimiento que supera el saldo pendiente; se conserva y puede tramitarse como devolución |
| Saldada | Estado de `CuentaPorCobrar` cuyo saldo pendiente es cero |
| Factura | Documento que refleja los conceptos efectivamente cobrados; tiene ciclo de vida separado del saldo de la cuenta |
| PendienteDeEmision / Emitida / Enviada | Estados documentales de Factura; no expresan el estado de pago de la cuenta |
| Pagador | Persona o entidad responsable de satisfacer la obligación; puede ser distinta del beneficiario del servicio |

## Control de Acceso — *Generic*

| Término | Significado acordado |
|---|---|
| AccesoMiembro | Agregado que conserva la habilitación y situación de acceso de un miembro |
| Derecho de acceso | Permiso recibido de la fuente autorizada, con sujeto, alcance, vigencia y restricciones |
| SituacionPago | Información financiera traducida al lenguaje de acceso mediante un contrato; no es una entidad financiera |
| EvaluacionAcceso | Decisión de acceso evaluada para una acción y un instante determinados |
| OperacionRegistroHuella | Operación identificable para dar de baja o volver a registrar una huella; solicitud y resultado confirmado son hechos distintos |

## Términos de otros subdominios

- **Membresía y plan**: paquete, cuota, beneficiarios, derechos y vigencia; su definición pertenece al subdominio de membresías.
- **Reserva**: asignación de espacio, sala o servicio para un sujeto o evento; su disponibilidad no la decide el contexto financiero.
- **Evento**: actividad programada que puede originar cargos y derechos temporales.
- **Notificación**: intento de entrega de un mensaje; no es el hecho de dominio que origina el aviso.
- **Cliente**, **pagador** y **miembro** no son sinónimos: nombrar el rol que interviene en cada operación.

## Reglas de uso

- Usar los nombres del contexto correspondiente en documentación, contratos y código.
- No llamar “Factura” a una obligación ni “pagada” a una cuenta sin describir la operación financiera que ocurrió.
- Distinguir pago informado, movimiento verificado, pago aplicado y exceso.
- No equiparar el estado financiero con una autorización o una actuación física de acceso.
