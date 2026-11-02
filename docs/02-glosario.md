# Glosario y lenguaje ubicuo

Consulta temática del [documento principal](00_ENTREGABLE_COMPLETO.md), con los diagramas, conceptos y reglas correspondientes.

### 3.2 Lenguaje de Facturación y Cobros

Los términos económicos describen obligaciones, movimientos y documentos diferentes. Una cuenta identifica lo que debe pagarse, mientras un pago identifica el dinero que efectivamente ingresó. La factura documenta los conceptos cobrados y conserva su propia relación con la emisión externa. Esta separación permite explicar pagos parciales, excesos y reenvíos sin alterar retrospectivamente el importe contratado.

| Término | Significado y ejemplo | Qué no representa | Regla asociada | Evento relacionado |
| --- | --- | --- | --- | --- |
| CuentaPorCobrar | Obligación de un pagador con conceptos, vencimiento y saldo | Factura fiscal ni membresía completa | Saldo no negativo y conceptos identificados | CuentaPorCobrarCreada |
| ConceptoCobro | Valor inmutable de un servicio: cuota, hora extra, alquiler, depósito o cargo por cancelación | Reserva completa o precio vigente recalculado | Origen único y precio comprometido conservado | CuentaPorCobrarCreada |
| Dinero | Monto decimal exacto y moneda | Número sin moneda ni tipo de cambio implícito | Operaciones de la misma moneda | PagoRegistrado |
| PagoInformado | Comprobante y referencia comunicados por el cliente | Confirmación del abono | No modifica saldo | PagoInformadoRegistrado |
| PagoVerificado | Transacción y abono conciliados con la obligación | Captura aislada o autorización bancaria sin abono | Identidad, monto y moneda deben coincidir | PagoVerificadoRegistrado |
| AplicacionPago | Parte del movimiento verificado que reduce el saldo de una cuenta | Nuevo cargo bancario o copia de la factura | Referencia aplicada una sola vez | PagoRegistrado |
| ExcesoPago | Parte verificada del movimiento que supera el saldo | Saldo negativo ni descuento | Se conserva como devolución pendiente | ExcesoPagoIdentificado |
| DevolucionExceso | Entidad interna que conserva autorización, importe y resultado de devolución | Segundo pago de la cuenta | No devolver más del exceso pendiente | DevolucionConfirmada |
| Factura | Documento interno de conceptos cobrados y relación con comprobante fiscal | Cuenta pendiente ni simple archivo adjunto | Total coincide con conceptos y pago aplicado | FacturaPreparada y FacturaEmitida |
| LineaFactura | Copia inmutable de descripción, cantidad e importe cobrados | Consulta dinámica de la tarifa actual | Total de factura igual a suma de líneas | FacturaPreparada |
| Pendiente | Estado de cuenta con saldo positivo | Estado de factura | Equivalencia entre estado y saldo | CuentaPorCobrarCreada |
| Saldada | Estado de cuenta con saldo cero | Miembro sin cualquier deuda ni acceso habilitado | Solo pagos verificados reducen el saldo | CuentaSaldada |
| PendienteDeEmision | Factura preparada, emisión externa por confirmar | Pago pendiente | Cuenta saldada y datos fiscales completos | FacturaPreparada |
| Emitida | Factura con referencia fiscal confirmada | Factura enviada al cliente | Resultado externo verificado | FacturaEmitida |
| Enviada | Factura emitida con constancia de envío | Lectura confirmada del destinatario | Envío acreditado sin segunda emisión | FacturaEnviada |

### 3.3 Lenguaje de Control de Acceso

AccesoMiembro conserva la identidad del permiso y el último resultado operativo confirmado. OperacionRegistroHuella identifica una actuación concreta, sus intentos y el responsable que puede ejecutarla. La observación registra si la huella está Presente, Ausente o en condición Desconocida. La evaluación de elegibilidad combina esa condición con el derecho y la situación económica antes de permitir una entrada.

| Término | Significado y ejemplo | Qué no representa | Regla asociada | Evento relacionado |
| --- | --- | --- | --- | --- |
| AccesoMiembro | Permiso identificado de ingreso de un miembro | Cuenta, plan completo ni dato biométrico | Cambios mediante su raíz | Eventos de acceso |
| Habilitado | Registro disponible y derecho operativo reconocido | Ingreso irrestricto a cualquier hora | Cada entrada evalúa horario y vigencia | AccesoRestablecido |
| SuspensionPendiente | Solicitud aceptada, baja todavía no confirmada | Registro ausente acreditado | No declarar suspensión por aceptar la tarea | SuspensionSolicitada |
| Suspendido | Baja confirmada o ausencia verificada mediante inspección | Membresía cancelada | Resultado físico acreditado | AccesoSuspendido |
| RestablecimientoPendiente | Alta en proceso o corrección de un alta que perdió elegibilidad | Ingreso ya permitido | Alta real y elegibilidad actual antes de habilitar | RestablecimientoSolicitado |
| SituacionPago | Valor traducido de obligaciones, saldo exigible y vencimientos del pagador que cubre el derecho | CuentaPorCobrar compartida entre contextos | Alcance completo y sujeto correctos | Reacción a CuentaSaldada |
| ConstanciaAviso | Resultado exitoso de aviso identificado por pagador y obligaciones | Solicitud de envío o mensaje fallido | Margen previo al corte | ResultadoEnvio |
| PeriodoTolerancia | Cinco días calendario desde vencimiento | Condonación ni extensión del vencimiento | Evaluación por obligación bloqueante | Evaluación de suspensión |
| OperacionRegistroHuella | Entidad interna de alta, baja por impago o baja correctiva | *Value object* sin identidad ni plantilla de huella | Un ejecutor adquirido por intento | Hechos finales de acceso |
| ObservacionRegistroHuella | Presente, Ausente o Desconocida, con fecha y evidencia | Garantía de ausencia de avería | No declarar un resultado actual por mensaje antiguo | Confirmación o reconciliación |
| EvaluacionAcceso | Resultado identificado para Suspender, Restablecer o Ingresar | Booleano aportado por el cliente | Contexto, versión y acción coherentes | Referencia de la decisión tomada |
| IncidenciaAcceso | Desviación operativa que requiere atención | Deuda nueva o permiso automático | Incertidumbre bloquea nuevas actuaciones incompatibles | Registro de incidencia |

### 3.4 Significados y operaciones entre subdominios

Un término cotidiano puede intervenir en ambos modelos sin significar lo mismo. La información económica se traduce a una situación de pago y no se transporta como una entidad compartida. La autorización de ingreso tampoco modifica el detalle de un cargo o el estado fiscal de una factura. Las expresiones siguientes se mantienen contextualizadas para que las conversaciones administrativas y las operaciones del sistema utilicen el mismo significado.

| Término | Facturación y Cobros | Control de Acceso | Uso acordado |
| --- | --- | --- | --- |
| AlDia | No existen obligaciones vencidas pendientes dentro del alcance consultado | Condición económica que se combina con vigencia y horario | Identificar pagador, obligaciones e instante |
| Confirmado | Transacción y abono verificados o emisión fiscal acreditada, según objeto | Alta o baja de huella efectivamente acreditada | Nombrar el hecho y la evidencia correspondiente |
| Vigente | Precio o condición contractual aplicable al servicio | Derecho del plan válido en el instante solicitado | Evitar usar la vigencia del plan como sustituto del saldo |
| Regularizado | Obligaciones exigibles cubiertas por aplicaciones de pago verificadas | Insumo que permite evaluar restablecimiento | No asumir que el pago modifica directamente la cerradura |
| Cliente | Titular pagador y receptor de factura | Miembro o sujeto de un derecho temporal identificado | Distinguir pagador de beneficiario del derecho |
| Deposito | Anticipo identificado como tramo económico de un evento | Dato que Agenda de eventos utiliza para confirmar un derecho temporal | No registrar el mismo tramo otra vez en el saldo final |

Los comandos expresan acciones y los eventos expresan hechos en pasado. RegistrarPagoConfirmado solicita una aplicación, mientras PagoRegistrado afirma que la aplicación fue aceptada y guardada. SolicitarRestablecimiento inicia una operación, mientras AccesoRestablecido acredita que el alta fue confirmada y la elegibilidad seguía siendo válida. Esta diferencia se mantiene en los *Application Services*, las APIs y los mensajes para evitar que un nombre anuncie un resultado antes de que ocurra.


---
