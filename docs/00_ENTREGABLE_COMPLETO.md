# Nodo — Facturación y Cobros y Control de Acceso

## Contenido

- [1. Introducción](#capitulo-01)
- [2. Contexto de Nodo, alcance y responsabilidades](#capitulo-02)
- [3. Información adicional y lenguaje ubicuo](#capitulo-03)
- [4. Procesos de facturación, cobro y acceso](#capitulo-04)
- [5. *Bounded Contexts* y *Context Maps*](#capitulo-05)
- [6. Integraciones y contratos de información](#capitulo-06)
- [7. Modelo de dominio de Facturación y Cobros](#capitulo-07)
- [8. Modelo de dominio de Control de Acceso](#capitulo-08)
- [9. Componentes y responsabilidades del modelo](#capitulo-09)
- [10. Casos de uso y coordinación de operaciones](#capitulo-10)

---

<a id="capitulo-01"></a>

## 1. Introducción

### 1.1 Resumen

Nodo combina la prestación de servicios de coworking con el alquiler de salas y la organización de eventos. Los clientes pueden contratar un pase por día, una membresía mensual flexible, un escritorio dedicado o un plan de equipo. Cada contratación determina derechos de uso, horarios, descuentos y posibles consumos adicionales. La gestión económica debe conservar esas condiciones y convertirlas en cargos comprensibles, pagos conciliados y facturas correctamente emitidas.

Facturación y Cobros y Control de Acceso son dos *Bounded Contexts* relacionados. El primero administra la liquidación de servicios, las cuentas por cobrar, la verificación de pagos, las devoluciones de excesos y la emisión de facturas mediante el procedimiento externo correspondiente. El segundo administra la habilitación del miembro y las operaciones manuales sobre el registro de huella. La coordinación permite que cada modelo mantenga sus reglas sin equiparar el saldo de una cuenta con el estado físico de una cerradura.

El modelo económico se concreta en los *aggregates* CuentaPorCobrar y Factura. El modelo operativo se concreta en el *aggregate* AccesoMiembro, que contiene las operaciones de registro y baja de huella. Cada uno presenta identidad, componentes internos, invariantes, transiciones y operaciones permitidas. Propongo un monolito modular para coordinar ambos modelos mediante contratos públicos, conservando sus entidades y transacciones separadas.

### 1.2 Problema

La operación de Nodo combina información sobre planes, reservas, servicios adicionales y movimientos de dinero. Un importe informado por el cliente puede corresponder a una cuota, un depósito de evento o varios conceptos agrupados. Una captura del comprobante acredita una comunicación del cliente, pero debe contrastarse con la transacción y el abono registrados por la empresa. Sin esta conciliación, un mismo movimiento puede aplicarse dos veces o una obligación puede parecer pagada antes de que exista confirmación suficiente.

La facturación presenta además una diferencia entre liquidar servicios y emitir el comprobante definitivo. La liquidación mensual reúne cuotas y consumos, mientras las operaciones puntuales pueden exigir un depósito o un cobro anticipado. El modelo genera la factura después de verificar el pago que completa la obligación correspondiente y conserva el detalle de los conceptos cobrados. Una modificación posterior de tarifas no puede cambiar el precio que ya se comprometió para una reserva o un servicio.

El acceso introduce otra responsabilidad y otra escala temporal. Recepción debe retirar o volver a registrar manualmente una huella, por lo que aceptar la solicitud no acredita que la actuación ya ocurrió. Una interrupción puede producir incertidumbre sobre el resultado o coincidir con un pago recibido durante la actuación. El modelo debe conservar el hecho financiero y el hecho físico por separado, y ofrecer una recuperación concreta para cada combinación.

### 1.3 Preguntas

- ¿Cómo transformar el paquete elegido, los derechos incluidos y los consumos confirmados en una obligación económica detallada y sin duplicaciones?
- ¿Cómo verificar un pago con el comprobante del cliente, la transacción externa y el abono de la empresa antes de aplicarlo?
- ¿Cómo emitir y enviar una factura por los conceptos efectivamente cobrados, manteniendo separado su estado del saldo pendiente?
- ¿Cómo gestionar un sobrepago y su devolución sin producir saldos negativos ni perder la identificación del movimiento original?
- ¿Cómo coordinar deuda, vigencia, horario y actuación sobre la huella sin declarar un resultado físico que todavía no se confirmó?
- ¿Cómo conservar las mismas reglas cuando la información llega por captura administrativa, API o mensaje de integración?

### 1.4 Objetivo general

Diseñar un modelo de dominio coherente para la facturación y el cobro de los servicios de Nodo y su coordinación con el control de acceso. El modelo debe representar tanto los cargos periódicos como las operaciones puntuales. Debe conservar el detalle del servicio contratado, la verificación de cada pago y la relación con la factura correspondiente. También debe asegurar que la suspensión y el restablecimiento describan actuaciones confirmadas sobre el registro de huella.

### 1.5 Objetivos específicos

- Delimitar las responsabilidades de Facturación y Cobros y Control de Acceso según los subdominios planteados para Nodo.
- Representar cuotas, horas adicionales, alquileres, cancelaciones cobrables, depósitos y servicios extra mediante conceptos identificados y valorados.
- Definir los *aggregates*, sus *entities*, sus *value objects*, sus invariantes y sus *domain events*.
- Establecer el procedimiento de conciliación de pagos, aplicación de abonos, identificación de excesos y devolución verificada.
- Definir el ciclo de la factura desde su preparación después del pago hasta la confirmación de emisión y envío.
- Especificar las operaciones de huella, los permisos, las confirmaciones y la recuperación frente a concurrencia o resultado incierto.
- Describir contratos mínimos de API y mensajería que preserven las reglas del dominio y permitan observar el estado de cada operación.

### 1.6 Alcance y límites

El alcance económico comprende todos los cargos que correspondan a los servicios contratados por los clientes. Incluye membresías, paquetes, horas de sala no cubiertas, alquileres puntuales, cancelaciones cobrables, depósitos de eventos y servicios adicionales. Las reglas de disponibilidad, aforo, aprobación del evento y cálculo de precios permanecen en los subdominios responsables, que entregan hechos e importes identificados a Facturación y Cobros. El modelo económico conserva las condiciones utilizadas y no vuelve a calcular una reserva histórica con una tarifa nueva.

El alcance de Control de Acceso comprende el permiso del miembro, su vigencia, las restricciones horarias y las operaciones sobre el registro de huella. Los accesos temporales de eventos se integran mediante derechos explícitos del subdominio Agenda de eventos. El modelo no almacena imágenes, plantillas ni características biométricas. La conexión con un dispositivo concreto y la operación del servicio tributario se encapsulan detrás de adaptadores, sin introducir sus protocolos dentro de las entidades del dominio.

---

<a id="capitulo-02"></a>

## 2. Contexto de Nodo, alcance y responsabilidades

### 2.1 Negocio, paquetes y servicios

Nodo obtiene ingresos de las membresías, el alquiler de salas y la contratación de eventos. Su oferta comprende pase por día, mensual flexible, escritorio dedicado y plan de equipo. Los paquetes pueden incluir horas de sala, descuentos y acceso en horario normal o continuo. La elección del paquete modifica los conceptos económicos y los derechos que deben intercambiar los subdominios, aunque no cambia la identidad del cliente.

Los consumos adicionales deben conservar un detalle que permita explicar qué se cobra y por qué. Las horas de sala que exceden las incluidas se cobran con las condiciones del plan y el precio comprometido para el servicio. Las cancelaciones de sala realizadas con menos de cuatro horas de anticipación generan el cargo de la franja completa; cuando faltan cuatro horas o más, no se genera penalidad. Los eventos incorporan depósito anticipado, alquiler del salón y servicios extra como catering o equipo audiovisual.

| Servicio o paquete | Información económica recibida | Tratamiento en Facturación y Cobros | Relación con Control de Acceso |
| --- | --- | --- | --- |
| Pase por día | Fecha, precio y recursos incluidos | Cuenta por el pase; extras como conceptos separados | Derecho limitado a fecha y horario concedidos |
| Mensual flexible | Ciclo, cuota, horas incluidas y descuento | Liquidación de cuota y consumos adicionales del período | Vigencia mensual y horario del plan |
| Escritorio dedicado | Puesto asignado, período y cuota | Cargo periódico identificado; servicios adicionales detallados | Derecho asociado a la membresía, sin asignar puestos desde Acceso |
| Plan de equipo | Titular pagador, miembros cubiertos, cuota y condiciones | Cuenta del titular y detalle de cargos; evitar facturar el mismo consumo a dos sujetos | Derechos individuales vinculados al plan y pagador correspondiente |
| Alquiler puntual de sala | Reserva, franja, precio y descuento comprometidos | Cargo del servicio o cancelación tardía; identidad del origen conservada | Derecho temporal si la contratación lo concede |
| Horas adicionales | Consumo que excede el derecho incluido y precio aplicable | Concepto diferenciado de horas extra; no recobrar las incluidas | El consumo no cambia por sí mismo el estado de la huella |
| Evento y depósito | Evento aprobado, importe total y depósito exigido | Cuenta del depósito y cuenta del saldo restante, sin duplicar el total | Derecho temporal confirmado por Agenda de eventos |
| Servicios extra de evento | Catering, audiovisual y otras líneas autorizadas | Descripción, cantidad, precio y autorización en cada concepto | No genera un derecho de ingreso independiente salvo instrucción del evento |

### 2.2 Subdominios y flujo de datos

El dominio de Nodo comprende la gestión de espacios, personas y eventos. Según los subdominios planteados, Reservas y disponibilidad concentra la coordinación del uso de espacios y recibe las condiciones de los planes, recursos y precios. Facturación y Cobros recibe los servicios valorizados y genera obligaciones económicas, mientras Control de Acceso combina derechos y situación de pago. Los intercambios se separan en contratación, liquidación y acceso para seguir el recorrido de cada dato sin superponer todas las relaciones.

**Figura 2.2a. Condiciones para contratar espacios y eventos.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart LR
  S["Espacios y recursos"] -->|"recurso y capacidad"| R["Reservas y disponibilidad"]
  S -->|"salón y capacidad"| E["Agenda de eventos"]
  M["Membresías y planes"] -->|"horas incluidas y descuento"| R
  T["Tarifas y precios"] -->|"precio y versión"| R
  T -->|"precio según aforo y extras"| E
```

**Figura 2.2b. Origen de los conceptos económicos.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart LR
  M["Membresías y planes"] -->|"cuota, pagador y ciclo"| F["Facturación y cobros"]
  R["Reservas y disponibilidad"] -->|"consumo, alquiler y cancelación"| F
  E["Agenda de eventos"] -->|"depósito, remanente y extras"| F
  T["Tarifas y precios"] -->|"cotización y descuento autorizado"| F
  F -->|"obligación y factura"| N["Notificaciones"]
  N -->|"resultado del envío"| F
```

**Figura 2.2c. Derechos, situación económica y avisos de acceso.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart LR
  M["Membresías y planes"] -->|"vigencia, beneficiario y horario"| A["Control de acceso"]
  R["Reservas y disponibilidad"] -->|"derecho temporal de reserva"| A
  E["Agenda de eventos"] -->|"derecho temporal del evento"| A
  F["Facturación y cobros"] -->|"obligaciones, saldo y vencimiento"| A
  A -->|"solicitud de aviso"| N["Notificaciones"]
  N -->|"constancia correlacionada"| A
```

| Subdominio | Clasificación | Responsabilidad y dato principal |
| --- | --- | --- |
| Reservas y disponibilidad | *Core* | Evitar solapes, mantener disponibilidad y comunicar reservas, consumos y cancelaciones |
| Membresías y planes | *Supporting* | Definir paquete, derechos, cuota, titular pagador, miembros cubiertos y vigencia |
| Agenda de eventos | *Supporting* | Gestionar aprobación, depósito requerido, contratación de extras y derechos temporales |
| Tarifas y precios | *Supporting* | Proporcionar precios y descuentos con vigencia y versión |
| Espacios y recursos | *Supporting* | Mantener recursos, capacidades y características operativas |
| Facturación y cobros | *Generic* | Liquidar cargos, conciliar pagos, devolver excesos y emitir facturas |
| Control de acceso | *Generic* | Evaluar ingreso y coordinar operaciones confirmadas sobre la huella |
| Notificaciones | *Generic* transversal | Entregar mensajes y devolver resultados de envío correlacionados |

Conservo Reservas y disponibilidad como *core* porque coordina el uso de recursos limitados y permite ofrecer una disponibilidad confiable. Los planes, los precios, los eventos y los recursos sostienen esa capacidad con reglas propias. Facturación y cobros y Control de acceso se clasifican como *generic* porque sus capacidades pueden apoyarse en soluciones estándar, aunque su integración requiere proteger las condiciones económicas y los derechos de Nodo. La clasificación expresa prioridad estratégica y no elimina las invariantes ni la necesidad de modelar las excepciones de cada subdominio.

Reservas y disponibilidad conserva la responsabilidad de impedir reservas solapadas y respetar los quince minutos de limpieza. Facturación y Cobros recibe el resultado económico de una reserva o cancelación, sin decidir disponibilidad. Control de Acceso recibe derechos y obligaciones, sin calcular tarifas ni editar el plan. Notificaciones entrega mensajes, pero la obligación de avisar antes de una suspensión sigue perteneciendo a la política de acceso.

### 2.3 Liquidación de servicios y derechos de acceso

La cuenta mensual consolida conceptos de un mismo pagador, moneda y período de liquidación. Los servicios puntuales pueden producir cuentas independientes cuando su cobro debe ocurrir antes del consumo o fuera del ciclo mensual. El depósito de un evento se identifica como un tramo del importe contratado, y el cargo final incluye únicamente el remanente y los extras que no se cobraron antes. La factura de cada tramo conserva la relación con el evento y permite reconstruir el total cobrado sin duplicarlo.

El acceso se evalúa respecto del sujeto que solicita ingresar y del pagador que cubre su derecho. En un plan de equipo, la deuda pertenece al titular y la afectación del acceso debe identificar explícitamente a los miembros cubiertos. Un visitante de un evento no se convierte en miembro para utilizar el mismo proceso de renovación. Los derechos temporales se consultan por su identidad y período, mientras AccesoMiembro mantiene el ciclo de los miembros habituales.

### 2.4 Responsabilidades y procedimiento

Administración gestiona la información económica, concilia los movimientos y emite las facturas. Recepción atiende al cliente, registra las comunicaciones de pago, entrega los documentos emitidos y realiza las actuaciones sobre el lector de huella. Dirección autoriza descuentos especiales, devoluciones y excepciones de acceso, además de establecer los permisos ordinarios de operación. El cliente contrata servicios, paga sus obligaciones y comunica los datos necesarios para identificar cada movimiento.

| Actor | Responsabilidad | Procedimiento | Registro |
| --- | --- | --- | --- |
| Administración | Cargos, cuentas, conciliación, devolución y emisión | Comprobar servicios y precios autorizados; liquidar conceptos; verificar transacción y abono; aplicar pagos; ejecutar devoluciones autorizadas; preparar y emitir facturas; solicitar suspensión o rehabilitación cuando corresponda | Cuenta, conceptos, referencias de pago, abonos, autorización de devolución, factura y solicitud de acceso |
| Recepción | Atención al cliente, entrega de documentos y operación física de acceso | Recibir y registrar comprobantes sin confirmar el abono; remitir incidencias económicas a Administración; entregar o enviar facturas emitidas; adquirir y ejecutar tareas de alta o baja de huella; confirmar el resultado real | Comunicación del cliente, constancia de entrega, operación, intento, ejecutor, dispositivo y evidencia |
| Dirección | Autorizaciones y política de operación | Aprobar descuentos especiales y devoluciones verificadas; autorizar el alta inicial y las excepciones; definir los permisos de Administración y Recepción | Autorización, importe o derecho afectado, alcance, vigencia y motivo |
| Cliente | Contratación y pago | Seleccionar paquete y servicios; comprobar el detalle cobrado; efectuar el pago; comunicar referencia y comprobante; recibir la factura y comunicar discrepancias | Contratación, titular pagador, movimiento informado y datos de facturación |

El cliente comunica el comprobante a Recepción o a través de un canal autorizado. Recepción registra esa comunicación y la remite a Administración, sin modificar el saldo ni acreditar por sí misma que existe un abono. Administración contrasta el movimiento con el servicio externo y el registro de ingresos de Nodo antes de aplicar el pago. Cuando la cuenta queda saldada, Administración confirma la emisión y Recepción entrega al cliente la factura correspondiente.

La coordinación del acceso comienza con la revisión económica de Administración. Una solicitud de suspensión debe identificar deuda, vencimiento y aviso, mientras una solicitud de rehabilitación exige regularización y derecho vigente. Recepción adquiere la tarea disponible, actúa sobre la cerradura y registra el resultado físico. Dirección interviene ante una excepción que exceda los permisos ordinarios, sin reemplazar la evidencia del pago o de la actuación.

---

<a id="capitulo-03"></a>

## 3. Información adicional y lenguaje ubicuo

### 3.1 Información adicional de la operación

La gestión económica y la atención física del cliente se coordinan mediante registros separados. Administración verifica los pagos y prepara las facturas, mientras Recepción registra las comunicaciones y ejecuta las operaciones de huella. Dirección autoriza las excepciones que modifican las condiciones económicas o de acceso. El comprobante del cliente, el abono de la empresa y el resultado de una actuación conservan identidades distintas para que puedan conciliarse sin confundirse.

| Aspecto | Información operativa | Consecuencia para el modelo |
| --- | --- | --- |
| Contratación | Cada paquete identifica derechos, titular pagador, vigencia y condiciones económicas | Los conceptos conservan paquete y precio aplicable; no se cobra una hora incluida como extra |
| Pago informado | El cliente comunica comprobante y referencia | Se registra la comunicación, pero todavía no se modifica el saldo |
| Pago verificado | Se comprueban transacción externa y abono en la empresa | Solo la evidencia conciliada permite aplicar el movimiento |
| Exceso pagado | Se devuelve el importe que supera la obligación, después de verificarlo y autorizarlo | Exceso y devolución quedan separados del saldo y del total facturado |
| Factura | Administración confirma la emisión después del pago completo; Recepción gestiona su entrega | Factura pertenece a Facturación y Cobros y tiene identidad y estado propios |
| Impago | El modelo aplica cinco días calendario desde vencimiento y aviso con al menos veinticuatro horas de anticipación | La fecha de corte requiere ambas condiciones y se calcula por obligación bloqueante |
| Horario | Horario normal configurado de lunes a viernes de 08:00 a 18:00; otros planes pueden conceder acceso continuo | El derecho del plan se evalúa por instante y no mediante una etiqueta genérica de miembro activo |
| Huella | Recepción da de baja y vuelve a registrar manualmente el registro de la cerradura | Solicitud y confirmación son operaciones distintas |
| Rehabilitación | Administración solicita después de la regularización y Recepción ejecuta el alta bajo la política de Dirección | Se exige regularización, derecho vigente y resultado físico confirmado |

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

<a id="capitulo-04"></a>

## 4. Procesos de facturación, cobro y acceso

### 4.1 Contratación, liquidación y decisiones de ingreso

El proceso económico comienza con la selección del paquete o la contratación de un servicio. Los subdominios responsables confirman el origen y el precio de cada cargo antes de incorporarlo a la liquidación. Después se verifica el movimiento comunicado por el cliente, se aplica el dinero y se prepara la factura cuando se completa el importe de la cuenta. El proceso de acceso reacciona a derechos y obligaciones, y mantiene separadas las decisiones económicas de las actuaciones físicas.

Administración recibe los cargos valorizados y comprueba su titular, origen y autorización antes de liquidarlos. El comprobante comunicado por el cliente se conserva mientras se verifica la transacción y el abono. Una discrepancia impide aplicar el movimiento y requiere conciliación, mientras un pago confirmado puede completar la cuenta y permitir la emisión. La entrega de la factura y la actuación sobre la huella se confirman por separado.

Los diagramas de *EventStorming* se leen de arriba hacia abajo y separan los procesos para conservar su orden temporal. Cada nota identifica su función con texto y color. Los eventos expresan hechos aceptados, mientras los comandos solicitan una acción y las políticas coordinan la respuesta a un hecho. Las ramas sin confirmación conservan una revisión o un estado pendiente y no anuncian un éxito.

| Elemento | Color | Significado |
| --- | --- | --- |
| Actor | Amarillo | Persona que solicita o ejecuta una acción |
| Comando | Azul | Acción que se solicita al modelo |
| Evento | Naranja | Hecho de negocio aceptado |
| Información | Verde | Datos utilizados para decidir |
| Regla o política | Violeta | Condición que debe cumplirse o respuesta coordinada |
| Sistema externo | Rosado claro | Servicio o mecanismo fuera de la transacción local |
| Incidencia | Rosado intenso | Diferencia o incertidumbre que requiere atención |

### 4.2 *EventStorming* de facturación y cobros

**Figura 4.2a. Liquidación de los servicios contratados.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  A["Actor<br/>Administración"]:::actor --> C["Comando<br/>Liquidar conceptos"]:::comando
  I["Información<br/>Servicios y precios aceptados"]:::informacion --> C
  C --> R{"Regla<br/>Origen único y total válido"}:::regla
  R --> E["Evento<br/>CuentaPorCobrarCreada"]:::evento
  E --> P["Política<br/>Comunicar importe y vencimiento"]:::politica
  P --> N["Externo<br/>Notificaciones"]:::externo
  classDef evento fill:#F7B267,stroke:#9C4B12,color:#17212B;
  classDef comando fill:#B4D8F0,stroke:#24638F,color:#17212B;
  classDef actor fill:#F8E58C,stroke:#8A7016,color:#17212B;
  classDef informacion fill:#C7E5BB,stroke:#427A30,color:#17212B;
  classDef regla fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef politica fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef externo fill:#F3CEE0,stroke:#9D5276,color:#17212B;
  classDef incidencia fill:#F6B9C9,stroke:#A12E50,color:#17212B;
```

**Figura 4.2b. Verificación y aplicación del pago.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  A["Actor<br/>Administración"]:::actor --> C["Comando<br/>Verificar movimiento"]:::comando
  I["Información<br/>Referencia y comprobante opcional"]:::informacion --> C
  C --> X["Externo<br/>Transacción y abono de Nodo"]:::externo
  X --> R{"Regla<br/>Identidad e importes coinciden"}:::regla
  R -->|"Sí"| C2["Comando<br/>Registrar pago confirmado"]:::comando
  C2 --> E["Evento<br/>PagoRegistrado"]:::evento
  R -->|"No o sin datos"| H["Incidencia<br/>Conciliar sin reducir saldo"]:::incidencia
  classDef evento fill:#F7B267,stroke:#9C4B12,color:#17212B;
  classDef comando fill:#B4D8F0,stroke:#24638F,color:#17212B;
  classDef actor fill:#F8E58C,stroke:#8A7016,color:#17212B;
  classDef informacion fill:#C7E5BB,stroke:#427A30,color:#17212B;
  classDef regla fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef politica fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef externo fill:#F3CEE0,stroke:#9D5276,color:#17212B;
  classDef incidencia fill:#F6B9C9,stroke:#A12E50,color:#17212B;
```

**Figura 4.2c. Saldo y devolución de un exceso.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  E["Evento<br/>PagoRegistrado"]:::evento --> R{"Regla<br/>¿El saldo llegó a cero?"}:::regla
  R -->|"Primera vez"| E2["Evento<br/>CuentaSaldada"]:::evento
  E --> X["Evento condicional<br/>ExcesoPagoIdentificado"]:::evento
  X --> A["Actor<br/>Dirección autoriza devolución"]:::actor
  A --> C["Comando<br/>Adquirir y devolver exceso"]:::comando
  C --> E3["Evento tras salida verificada<br/>DevolucionConfirmada"]:::evento
  classDef evento fill:#F7B267,stroke:#9C4B12,color:#17212B;
  classDef comando fill:#B4D8F0,stroke:#24638F,color:#17212B;
  classDef actor fill:#F8E58C,stroke:#8A7016,color:#17212B;
  classDef informacion fill:#C7E5BB,stroke:#427A30,color:#17212B;
  classDef regla fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef politica fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef externo fill:#F3CEE0,stroke:#9D5276,color:#17212B;
  classDef incidencia fill:#F6B9C9,stroke:#A12E50,color:#17212B;
```

**Figura 4.2d. Preparación, emisión y entrega de la factura.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  E["Evento<br/>CuentaSaldada"]:::evento --> P["Política<br/>Preparar con datos fiscales completos"]:::politica
  P --> E2["Evento<br/>FacturaPreparada"]:::evento
  E2 --> C["Comando de Administración<br/>Adquirir intento y emitir"]:::comando
  C --> X["Externo<br/>Emisión tributaria"]:::externo
  X --> E3["Evento tras verificar resultado<br/>FacturaEmitida"]:::evento
  E3 --> C2["Comando de Recepción<br/>Enviar factura emitida"]:::comando
  C2 --> E4["Evento con constancia de envío<br/>FacturaEnviada"]:::evento
  classDef evento fill:#F7B267,stroke:#9C4B12,color:#17212B;
  classDef comando fill:#B4D8F0,stroke:#24638F,color:#17212B;
  classDef actor fill:#F8E58C,stroke:#8A7016,color:#17212B;
  classDef informacion fill:#C7E5BB,stroke:#427A30,color:#17212B;
  classDef regla fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef politica fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef externo fill:#F3CEE0,stroke:#9D5276,color:#17212B;
  classDef incidencia fill:#F6B9C9,stroke:#A12E50,color:#17212B;
```

La cuenta admite cargos de todos los servicios del alcance y no depende de que el origen sea una renovación. La verificación compara el comprobante comunicado con el movimiento externo y el abono de la empresa. La parte que excede la obligación conserva un expediente de devolución y no aumenta el total facturado. Una respuesta fiscal incierta impide confirmar la emisión y exige consultar el resultado antes de repetir la actuación.

### 4.3 *EventStorming* de suspensión y rehabilitación

**Figura 4.3a. Solicitud y confirmación de suspensión.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  A["Actor<br/>Administración"]:::actor --> C["Comando<br/>Evaluar y solicitar suspensión"]:::comando
  I["Información<br/>Deuda, cinco días y aviso de 24 h"]:::informacion --> C
  C --> E["Evento si cumple la política<br/>SuspensionSolicitada"]:::evento
  E --> C2["Comando<br/>Adquirir y guardar ejecución"]:::comando
  C2 --> X["Externo<br/>Recepción revalida y retira huella"]:::externo
  X --> C3["Comando con evidencia<br/>Confirmar suspensión"]:::comando
  C3 --> E2["Evento<br/>AccesoSuspendido"]:::evento
  classDef evento fill:#F7B267,stroke:#9C4B12,color:#17212B;
  classDef comando fill:#B4D8F0,stroke:#24638F,color:#17212B;
  classDef actor fill:#F8E58C,stroke:#8A7016,color:#17212B;
  classDef informacion fill:#C7E5BB,stroke:#427A30,color:#17212B;
  classDef regla fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef politica fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef externo fill:#F3CEE0,stroke:#9D5276,color:#17212B;
  classDef incidencia fill:#F6B9C9,stroke:#A12E50,color:#17212B;
```

**Figura 4.3b. Rehabilitación y corrección de un alta.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  I["Información<br/>Regularización y derecho vigente"]:::informacion --> C["Comando de Administración<br/>Solicitar restablecimiento"]:::comando
  C --> E["Evento<br/>RestablecimientoSolicitado"]:::evento
  E --> X["Externo<br/>Recepción adquiere y registra huella"]:::externo
  X --> R{"Regla<br/>Alta real y evaluación actual"}:::regla
  R -->|"Favorable"| E2["Evento<br/>AccesoRestablecido"]:::evento
  R -->|"Desfavorable o NoEvaluable"| H["Incidencia<br/>Cerrar alta sin habilitar"]:::incidencia
  H --> P["Política<br/>Otra operación de retiro correctivo"]:::politica
  classDef evento fill:#F7B267,stroke:#9C4B12,color:#17212B;
  classDef comando fill:#B4D8F0,stroke:#24638F,color:#17212B;
  classDef actor fill:#F8E58C,stroke:#8A7016,color:#17212B;
  classDef informacion fill:#C7E5BB,stroke:#427A30,color:#17212B;
  classDef regla fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef politica fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef externo fill:#F3CEE0,stroke:#9D5276,color:#17212B;
  classDef incidencia fill:#F6B9C9,stroke:#A12E50,color:#17212B;
```

**Figura 4.3c. Recuperación de una actuación incierta.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  H["Incidencia<br/>Resultado físico incierto"]:::incidencia --> P["Política<br/>Conservar intento y ejecutor"]:::politica
  P --> C["Comando<br/>Consultar sin reenviar actuación"]:::comando
  C --> R{"Regla<br/>¿Hay evidencia concluyente?"}:::regla
  R -->|"Efecto acreditado"| C2["Comando<br/>Confirmar resultado real"]:::comando
  R -->|"No efecto y cese reconocido"| C3["Comando<br/>Cerrar intento y reevaluar"]:::comando
  R -->|"No"| H2["Incidencia<br/>Mantener revisión e inspección"]:::incidencia
  classDef evento fill:#F7B267,stroke:#9C4B12,color:#17212B;
  classDef comando fill:#B4D8F0,stroke:#24638F,color:#17212B;
  classDef actor fill:#F8E58C,stroke:#8A7016,color:#17212B;
  classDef informacion fill:#C7E5BB,stroke:#427A30,color:#17212B;
  classDef regla fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef politica fill:#D8C8EB,stroke:#755093,color:#17212B;
  classDef externo fill:#F3CEE0,stroke:#9D5276,color:#17212B;
  classDef incidencia fill:#F6B9C9,stroke:#A12E50,color:#17212B;
```

Las operaciones pendientes se completan utilizando la misma identidad y no vuelven a solicitarse desde un estado incompatible. La adquisición se guarda antes de entregar la tarea y solo el ganador puede actuar. Cuando el resultado es incierto, la consulta y la inspección sustituyen al reenvío automático. Si el alta ya ocurrió y se perdió elegibilidad, el sistema conserva ese hecho, cierra el alta y crea otra operación para la baja correctiva.

### 4.4 *Domain Storytelling* de cobro y acceso

El cliente recibe una liquidación con el detalle de los servicios y comunica el comprobante a Recepción. Administración contrasta esa comunicación con la transacción y el abono, y decide si corresponde aplicar el movimiento. Dirección autoriza los descuentos o devoluciones que requieren una decisión especial. Después de completar el pago, Administración confirma la emisión y Recepción entrega la factura al cliente.

**Figura 4.4a. Comunicación y conciliación del movimiento.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  actor C as Cliente
  actor R as Recepción
  actor A as Administración
  participant V as Verificación y abonos
  C->>R: 1. Presentar referencia y comprobante
  R->>A: 2. Remitir comunicación registrada
  A->>V: 3. Consultar transacción y abono
  V-->>A: 4. Entregar evidencia o discrepancia
  A-->>C: 5. Comunicar aplicación o revisión
```

**Figura 4.4b. Emisión y entrega del documento.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  actor A as Administración
  participant X as Emisión tributaria
  actor R as Recepción
  actor C as Cliente
  A->>X: 1. Emitir con intento adquirido
  X-->>A: 2. Devolver resultado verificable
  A->>R: 3. Entregar factura emitida
  R->>C: 4. Entregar o enviar documento
  Note over R,C: Conservar constancia de entrega
```

La solicitud de acceso utiliza la situación económica vigente y el derecho del miembro. Administración inicia la suspensión o rehabilitación que corresponda, sin declarar que la cerradura ya cambió. Recepción recibe una tarea con ejecutor e intento adquiridos, realiza la actuación y registra su evidencia. La confirmación actualiza AccesoMiembro y permite comunicar el resultado, mientras una respuesta incierta conserva la tarea para consulta e inspección.

**Figura 4.4c. Actuación de Recepción sobre la huella.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  actor A as Administración
  participant C as Control de Acceso
  actor R as Recepción
  participant H as Lector de huella
  A->>C: 1. Solicitar actuación evaluada
  R->>C: 2. Adquirir operación disponible
  C-->>R: 3. Dar permiso tras guardar y revalidar
  R->>H: 4. Registrar o retirar huella
  R->>C: 5. Confirmar evidencia del resultado
  C-->>A: 6. Comunicar resultado o revisión
```

---

<a id="capitulo-05"></a>

## 5. *Bounded Contexts* y *Context Maps*

### 5.1 Fronteras de los modelos

Facturación y Cobros y Control de Acceso representan dos *Bounded Contexts* con lenguaje y reglas propios. El primero protege obligaciones, movimientos de dinero, devoluciones y documentos cobrados. El segundo protege derechos de ingreso, restricciones horarias y actuaciones sobre la huella. La coordinación se apoya en contratos de información y no en compartir las entidades de ambos modelos.

| Señal de frontera | Facturación y Cobros | Control de Acceso | Consecuencia |
| --- | --- | --- | --- |
| Significado | Cuenta saldada y factura emitida | Acceso habilitado y baja confirmada | El mismo término confirmado debe indicar su objeto |
| Reglas | Moneda, saldo, referencia y total facturado | Vigencia, horario, aviso y resultado físico | Cada raíz protege invariantes de su modelo |
| Responsabilidad | Administración liquida, concilia y emite; Dirección autoriza excepciones económicas | Recepción configura huella bajo política de Dirección | Los permisos se asignan por responsabilidad y no por acceso indistinto al modelo |
| Cambio de proceso | Se completa el pago y se prepara el documento | Se reevalúa el derecho y se completa una operación física | Los eventos coordinan transacciones separadas |
| Recuperación | Conciliar abono, evitar otra aplicación o devolución | Consultar ejecutor, cesar autorización e inspeccionar | El tratamiento de incertidumbre conserva el significado del efecto |

Un único contexto con varias raíces sería técnicamente posible. Elijo dos contextos porque las obligaciones económicas y las actuaciones sobre la huella tienen significados, responsables y formas de recuperación diferentes. Prefiero un monolito modular porque permite mantener ambos modelos en una aplicación, separar sus interfaces y conservar la persistencia lógica de cada módulo. Las llamadas al proveedor de pagos, al mecanismo fiscal y al lector permanecen fuera de la transacción local de cada raíz.

### 5.2 *Context Map* general

Facturación y Cobros se relaciona con planes, reservas, precios y eventos mediante contratos que identifican al responsable de cada información. Control de Acceso utiliza los derechos y la situación económica para decidir sobre el ingreso. El proveedor de pagos, la emisión tributaria y el lector de huella se conectan mediante adaptadores, sin convertirse en nuevos subdominios. En las figuras 5.2a y 5.2b, planes, reservas y eventos se relacionan mediante *Customer/Supplier*; OHS y PL identifican *Open Host Service* y *Published Language* de Tarifas y precios. Las flechas van del proveedor del modelo al consumidor, y las discontinuas de la figura 5.2c muestran el contrato transversal de Notificaciones.

**Figura 5.2a. Proveedores de Facturación y Cobros.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart LR
  M["Membresías y planes"] --> F["Facturación y Cobros"]
  R["Reservas y disponibilidad"] --> F
  E["Agenda de eventos"] --> F
  T["Tarifas y precios"] -->|"OHS y PL"| F
```

**Figura 5.2b. Proveedores de derechos de Control de Acceso.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart LR
  M["Membresías y planes"] --> A["Control de Acceso"]
  R["Reservas y disponibilidad"] --> A
  E["Agenda de eventos"] --> A
```

**Figura 5.2c. Contrato financiero y servicio transversal.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  F["Facturación y Cobros<br/>Proveedor de situación financiera"] -->|"contrato público"| L["Anti-Corruption Layer<br/>de Control de Acceso"]
  L -->|"SituacionPago"| A["Control de Acceso"]
  N["Notificaciones<br/>Open Host Service y <i>Published Language</i>"] -.->|"contrato consumido"| F
  N -.->|"contrato consumido"| A
```

### 5.3 Modelo interno de cada *Bounded Context*

**Figura 5.3a. Coordinación dentro de Facturación y Cobros.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  participant C as CuentaPorCobrar
  participant A as Aplicación financiera
  participant Q as Consulta de cuenta
  participant F as Factura
  C-->>A: CuentaSaldada después del commit
  A->>Q: Obtener conceptos cerrados
  Q-->>A: Devolver copia de valores y cuentaId
  A->>F: Preparar en otra transacción
  F-->>A: FacturaPreparada al guardar
```

**Figura 5.3b. Traducción y decisión dentro de Control de Acceso.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  F["Contrato financiero público"] --> L["<i>Anti-Corruption Layer</i>"]
  L --> S["SituacionPago"]
  S --> E["EvaluadorDeIngreso"]
  P["Derecho, aviso y política"] --> E
  E -->|"EvaluacionAcceso"| A["AccesoMiembro"]
  A -->|"solicitud y resultado por la aplicación"| H["Puerto de operación de huella"]
```

La cuenta y la factura son raíces distintas dentro de Facturación y Cobros. El evento de cuenta saldada provoca una consulta de los conceptos cerrados y permite preparar la factura sin compartir una instancia mutable de la cuenta. La *Anti-Corruption Layer* pertenece a Control de Acceso y convierte la respuesta pública en SituacionPago. AccesoMiembro utiliza ese valor para decidir, mientras el estado de Factura no se interpreta como sustituto de la confirmación del abono.

### 5.4 Responsabilidades y límites de integración

Cada raíz agrupa únicamente los datos que deben modificarse de forma consistente en una transacción. CuentaPorCobrar conserva saldo, aplicaciones y excesos, Factura mantiene sus líneas y estado fiscal, y AccesoMiembro conserva la operación y la evidencia física. Mantengo los planes, recursos y contrataciones fuera de estas raíces y los relaciono por identificador mediante sus contratos públicos. Una modificación de saldo no cambia el plan, la reserva ni el registro de huella.

| Subdominio responsable | Información entregada | Límite del intercambio |
| --- | --- | --- |
| Membresías y planes | Cuota, titular pagador, beneficiarios, vigencia y horario | Facturación no redefine derechos y Acceso no modifica el ciclo contratado |
| Reservas y disponibilidad | Consumo, cancelación cobrable, precio comprometido y derecho temporal | La disponibilidad, el solapamiento y la limpieza se resuelven antes del cargo |
| Agenda de eventos | Depósito, remanente, extras autorizados y derecho temporal | La aprobación y el aforo permanecen en el modelo del evento |
| Tarifas y precios | Cotización, descuento autorizado y versión | Una tarifa nueva no modifica conceptos ya liquidados |
| Espacios y recursos | Identidad y condiciones del recurso por su contratación | La cuenta referencia el servicio y no incorpora el inventario completo |
| Facturación y Cobros | Obligaciones, saldos, vencimientos y confirmaciones económicas | Acceso recibe SituacionPago mediante traducción y no consulta el repositorio financiero |
| Control de Acceso | Solicitudes, resultados de huella y evaluación de ingreso | El resultado físico no sustituye el abono ni cambia el total de Factura |
| Notificaciones | Resultado de envío y constancia correlacionada | El servicio entrega mensajes y no decide saldo o elegibilidad |

Un derecho temporal identifica sujeto, origen, intervalo y condiciones de contratación cumplidas. Su evaluación no requiere convertir al visitante en una membresía ni almacenar el aforo del evento en AccesoMiembro. Recepción puede realizar una apertura supervisada cuando el derecho confirmado lo permite. Los cambios económicos se tramitan en Facturación y Cobros y se comunican mediante el contrato correspondiente.

---

<a id="capitulo-06"></a>

## 6. Integraciones y contratos de información

### 6.1 Gobierno de los contratos

La integración distingue quién controla el modelo y quién solicita una operación. Un proveedor puede definir el contrato aunque las peticiones viajen desde el consumidor hacia él. Esta diferencia es relevante en Notificaciones, que publica el servicio de entrega mientras Facturación y Cobros y Control de Acceso lo consumen. El sentido de una llamada HTTP no se utiliza como sustituto de la relación entre modelos.

| Relación | Proveedor del modelo | Consumidor | Patrón elegido y responsabilidad |
| --- | --- | --- | --- |
| Paquetes y derechos | Membresías y planes | Facturación y Cobros; Control de Acceso | *Customer/Supplier* con datos mínimos de cuota, pagador, vigencia y restricciones |
| Consumos y cancelaciones | Reservas y disponibilidad | Facturación y Cobros | *Customer/Supplier*; el consumo y su precio comprometido pertenecen a Reservas |
| Eventos y tramos | Agenda de eventos | Facturación y Cobros; Control de Acceso | *Customer/Supplier*; depósito, saldo, extras y derecho temporal identificados |
| Precios aplicables | Tarifas y precios | Facturación y Cobros y responsables de contratación | *Open Host Service* y *Published Language* para cotización versionada |
| Situación económica | Facturación y Cobros | Control de Acceso | *Anti-Corruption Layer* en el consumidor; ninguna entidad financiera compartida |
| Entrega de mensajes | Notificaciones | Facturación y Cobros; Control de Acceso | *Open Host Service* y *Published Language*; solicitud hacia Notificaciones y resultado inverso |
| Verificación externa | Servicio de pagos y registro de abonos | Facturación y Cobros | Adaptador y traducción de transacción externa a evidencia verificada |
| Emisión externa | Mecanismo tributario operado por Administración | Facturación y Cobros | Adaptador de captura y consulta de resultado; el documento interno sigue siendo Factura |
| Configuración de huella | Recepción y controlador de puerta | Control de Acceso | Puerto de actuación y resultado con adquisición previa de ejecutor |

Los contratos de los vecinos entregan hechos e importes que ya tienen un responsable de negocio. Facturación no necesita importar la entidad Reserva para registrar un alquiler, y Acceso no necesita cargar CuentaPorCobrar para evaluar el ingreso. Las traducciones se justifican por diferencias de significado y no por la mera existencia de una API. El modelo conserva sus reglas aunque un proveedor cambie la forma de entregar un mensaje o una constancia.

### 6.2 Vista de dependencias y mensajes

**Figura 6.2a. Efectos externos y sus resultados.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  P["Proveedor de pagos"] -->|"transacción consultada"| F["Aplicación financiera"]
  B["Registro empresarial"] -->|"abono conciliable"| F
  F -->|"intento de emisión adquirido"| X["Mecanismo tributario"]
  X -->|"referencia y constancia"| F
  A["Aplicación de Acceso"] -->|"permiso adquirido"| H["Recepción y lector de huella"]
  H -->|"evidencia física"| A
```

**Figura 6.2b. Solicitudes y resultados de Notificaciones.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  participant F as Facturación y Cobros
  participant N as Notificaciones
  participant A as Control de Acceso
  F->>N: SolicitarEnvio de obligación o factura
  N-->>F: ResultadoEnvio correlacionado
  A->>N: SolicitarEnvio de aviso de acceso
  N-->>A: ResultadoEnvio correlacionado
  Note over F,A: La solicitud de envío no acredita entrega
```

La figura 6.2a muestra las solicitudes y evidencias de los efectos externos. La figura 6.2b distingue las solicitudes de envío de sus resultados correlacionados. SolicitarEnvio es un comando del servicio, mientras FacturaEmitida y AccesoSuspendido son *domain events* de los modelos propietarios. El comando puede fallar sin deshacer el hecho que lo originó y su recuperación utiliza una identidad de envío estable.

### 6.3 Datos mínimos por frontera

| Contrato | Campos mínimos | Comprobación del consumidor |
| --- | --- | --- |
| PaqueteContratado | paqueteId, membresiaId, pagadorId, beneficiarios, ciclo, cuota, derechos, horario y versión | El pagador y el período corresponden a la contratación |
| CargoServicio | origenId, tipo, pagadorId, cantidad, precio, descuento, neto, moneda y precioVersion | Origen único, detalle completo e importe autorizado |
| TramoEvento | eventoId, tramoId, depósito o saldo, importe y referencias de tramos previos | No volver a incluir el depósito en el remanente |
| MovimientoVerificable | proveedor, referencia, titular, importe, moneda, estado, abonoId y fechas | Transacción y abono coincidentes; no solo comprobante comunicado |
| SituacionFinanciera | pagadorId, obligaciones relevantes, saldos, vencimientos, conceptos bloqueantes, completa, consultadoEn y versiones | Alcance completo, sujeto correcto y consulta reciente |
| DerechoIngreso | sujetoId, pagadorId cuando aplica, origen, vigencia, horario, restricciones y versión | Derecho válido en el instante solicitado |
| PermisoOperacionHuella | accesoId, operacionId, intentoId, ejecutorId, tipo, dispositivoId y versión adquirida | Existe adquisición guardada y permiso vigente |
| ResultadoOperacion | Identidad de permiso, resultado, operador, ocurridoEn, recibidoEn y evidenciaId | Tipo y ejecutor coinciden; incertidumbre no se transforma en éxito |
| ResultadoEmision | facturaId, intentoId, referenciaFiscal, total, moneda y constancia | El documento y el importe coinciden con la factura preparada |

La consulta financiera identifica todas las obligaciones que afectan al derecho evaluado. Un resultado incompleto no se interpreta como ausencia de deuda y una cuenta saldada no acredita que las restantes también lo estén. La aplicación obtiene una nueva consulta en cada fase que precede al efecto y al confirmar un alta. Establezco una antigüedad máxima de treinta segundos por fase para limitar el uso de información desactualizada, aunque la consulta no crea una transacción global con el proveedor ni la cerradura.

### 6.4 Estructura básica de API

La interfaz puede exponerse mediante una API *REST* sobre HTTPS con documentos JSON. Los controladores validan estructura y permisos, y delegan las decisiones de negocio en los *Application Services*. Los comandos de escritura incorporan una clave de idempotencia y las actualizaciones administrativas utilizan la versión esperada del objeto. Las rutas siguientes definen una estructura básica y no implican una integración ya desplegada.

| Método y ruta conceptual | Uso | Condición esencial |
| --- | --- | --- |
| `POST /api/v1/cuentas` | Crear obligación con conceptos liquidados | Permiso administrativo y origen de cargo no duplicado |
| `POST /api/v1/pagos/informados` | Recibir referencia y comprobante del cliente | Registrar comunicación sin reducir saldo |
| `POST /api/v1/pagos/{pagoId}/verificaciones` | Conciliar proveedor, movimiento y abono | Servicio administrativo autorizado; datos externos consultados por el servidor |
| `POST /api/v1/cuentas/{cuentaId}/aplicaciones` | Aplicar movimiento ya verificado | Referencia, moneda, cuenta y verificación coherentes |
| `POST /api/v1/devoluciones/{devolucionId}/aprobacion` | Autorizar exceso verificado | Permiso de Dirección y monto dentro del exceso |
| `POST /api/v1/devoluciones/{devolucionId}/ejecucion` | Adquirir y ejecutar devolución | Un responsable adquirido y resultado conciliado |
| `POST /api/v1/facturas` | Preparar documento desde cuenta saldada | Unicidad por cuenta y tipo de documento |
| `POST /api/v1/facturas/{facturaId}/emisiones` | Adquirir intento de emisión | Factura pendiente, sin otro ejecutor activo |
| `POST /api/v1/facturas/{facturaId}/resultados-emision` | Confirmar referencia fiscal | Evidencia del intento adquirido y total coincidente |
| `GET /api/v1/pagadores/{pagadorId}/situacion` | Consultar obligaciones y estado económico | Alcance autorizado, completitud, tiempos y versiones |
| `POST /api/v1/accesos/{accesoId}/suspensiones` | Solicitar suspensión evaluada | Plazo, aviso y deuda de la política |
| `POST /api/v1/accesos/{accesoId}/restablecimientos` | Solicitar alta evaluada | Regularización, vigencia y permiso de solicitud de Administración |
| `POST /api/v1/operaciones/{operacionId}/adquisicion` | Asignar un único ejecutor | Versión esperada y estado Solicitada |
| `POST /api/v1/operaciones/{operacionId}/resultados` | Registrar evidencia del acto | Operación, intento, ejecutor y dispositivo correlacionados |
| `POST /api/v1/ingresos/evaluaciones` | Consultar elegibilidad del sujeto y derecho en un instante | Distinguir miembro y derecho temporal confirmado |
| `GET /api/v1/accesos/{accesoId}` | Consultar estado, observación e incidencia | No ocultar incertidumbre bajo un estado histórico |

Los errores distinguen solicitud mal formada, falta de permiso, objeto inexistente, conflicto de versión y rechazo de dominio. Una petición aceptada para actuación externa puede responder con estado pendiente y un identificador consultable. El cliente no reenvía un pago o una actuación física para intentar resolver por sí mismo un estado incierto. Una respuesta de conflicto exige consultar la versión actual y decidir si el resultado ya se produjo antes de insistir.

La cabecera `Idempotency-Key` identifica la intención y se reutiliza en todos sus reintentos. El servidor reserva atómicamente esa clave junto con la función solicitada y una huella del contenido, antes de entregar un efecto externo. Una repetición completa devuelve el resultado conservado, una repetición activa devuelve pendiente con una ruta de consulta y una clave con contenido distinto produce conflicto. Las identidades no caducan mientras la operación o su cadena de reentrega puedan volver a producir el mismo efecto.

| Respuesta HTTP | Significado del contrato |
| --- | --- |
| `200` | Consulta o resultado conocido de una operación completada |
| `201` | Recurso creado con identidad consultable |
| `202` | Coordinación aceptada o repetición de intención todavía activa; incluye ruta de estado |
| `400` | Estructura de entrada inválida |
| `401` o `403` | Actor no autenticado o función no autorizada |
| `404` | Objeto inexistente dentro del alcance autorizado |
| `409` | Conflicto de versión, asociación o reutilización incompatible de clave |
| `422` | Datos bien formados que incumplen una regla del dominio |
| `503` | Dependencia no disponible; no implica ausencia de efecto anterior |

Los errores utilizan una estructura común con codigo, mensaje, correlationId y detalles de validación no sensibles. Las respuestas de servicios externos se validan en el adaptador antes de convertirse en valores de dominio. Los campos que se incorporen sin cambiar significado se agregan de forma compatible y los cambios de significado requieren una nueva versión de contrato. La situación financiera resumida cubre siempre el alcance completo; si el detalle se pagina, una página aislada nunca se interpreta como toda la deuda.

### 6.5 *Webhooks*, *WebSocket* y entrega de eventos

El proveedor puede comunicar cambios mediante un *webhook* firmado. El receptor comprueba firma, identidad del mensaje, transacción y antigüedad antes de guardar la recepción. Después consulta el estado confiable del movimiento y verifica su abono en la empresa, en lugar de considerar suficiente el texto recibido. La entrega repetida se reconoce mediante la identidad externa y la referencia financiera permanece como protección de negocio adicional.

Una vista administrativa puede recibir actualizaciones mediante *WebSocket* o *Server-Sent Events*. El canal comunica que cambió una cuenta, factura u operación y permite refrescar una vista autorizada. No concede permiso para aplicar un pago, emitir otra factura o ejecutar una baja de huella. Después de una reconexión, el cliente consulta el estado actual mediante la API y no supone que recibió todos los mensajes intermedios.

La publicación entre módulos utiliza registros de salida y recepción asociados a la transacción local. Un patrón *outbox* conserva los mensajes que deben salir después del guardado, y un registro *inbox* identifica los ya recibidos. La entrega puede ocurrir más de una vez y cada consumidor mantiene idempotencia según la identidad del hecho y su clave de negocio. Este mecanismo evita perder coordinación por una caída después del pago sin prometer una sola entrega física del mensaje.

```json
{
  "messageId": "MSG-001",
  "eventoId": "EVT-001",
  "tipo": "CuentaSaldada",
  "versionContrato": 1,
  "cuentaId": "CPC-001",
  "pagadorId": "CLI-001",
  "versionOrigen": 4,
  "ocurridoEn": "2026-11-06T12:00:00-04:00",
  "correlationId": "COB-001"
}
```

### 6.6 Emisión y actuación física

Factura pertenece al modelo financiero aunque su emisión definitiva requiera un mecanismo externo. Administración adquiere el intento, opera el portal o adaptador autorizado y conserva la constancia del resultado. Un conector fiscal por API puede incorporarse después sin cambiar las invariantes de Factura. Si el resultado queda incierto, se consulta por la identidad del intento y la referencia disponible antes de solicitar otra emisión.

La huella se configura mediante un puerto de operación y confirmación. Su contrato exige una única asignación persistida antes de entregar el permiso al operador. La plantilla permanece en el dispositivo y las evidencias administrativas no contienen biometría. La validación del horario debe aplicarse a la apertura, mientras la programación del registro puede realizarse fuera de la franja si la rehabilitación corresponde.

Los adaptadores deben poder consultar por la identidad de una transacción, devolución o intento de emisión. La configuración identifica proveedor, autenticación, estados reconocidos y ubicación protegida de las evidencias. El controlador de puerta debe aplicar el horario del derecho y permitir verificar el resultado de un alta o retiro; si no ofrece esas capacidades, el ingreso requiere supervisión de Recepción. La selección concreta de proveedor, dispositivo y formato de constancia no cambia las condiciones que deben cumplirse para confirmar cada resultado.

### 6.7 Seguridad y protección de información

El servidor autentica a los operadores y autoriza cada función de acuerdo con sus permisos. Los secretos del proveedor y la firma de mensajes permanecen en adaptadores protegidos y no se exponen al cliente. Los comprobantes y datos de facturación se consultan por referencia mediante permisos específicos, evitando incluirlos completos en los eventos de integración. El registro de actividad conserva actor, acción, objeto, versiones y evidencia mínima para reconstruir la decisión sin almacenar información biométrica.

---

<a id="capitulo-07"></a>

## 7. Modelo de dominio de Facturación y Cobros

### 7.1 *Aggregates* y fronteras de consistencia

Facturación y Cobros contiene dos *aggregates*: CuentaPorCobrar y Factura. CuentaPorCobrar protege la obligación, la aplicación de movimientos verificados y los excesos que deben devolverse. Factura protege el detalle del comprobante, su identidad interna y su relación con la emisión externa. Ambos permanecen en el mismo *Bounded Context*, pero se guardan en transacciones independientes porque confirmar un pago y emitir un comprobante son actuaciones diferentes.

CuentaPorCobrar es la *aggregate root* de la obligación económica. Contiene una colección inmutable de ConceptoCobro, aplicaciones de pago y las entidades internas DevolucionExceso que se originan por movimientos que exceden el saldo. El pagador, el paquete, la reserva, el evento y el período se referencian por identificador y no se incorporan como objetos completos. La raíz concentra todas las operaciones que pueden modificar saldo, aplicaciones y devolución pendiente.

Factura es la *aggregate root* del documento que corresponde a una cuenta saldada. Contiene LineaFactura, DatosFacturacion, referencia a pagos aplicados y el registro de emisión y envío. Sus líneas se copian desde los conceptos liquidados y mantienen el precio comprometido aunque cambien las tarifas. La numeración fiscal y la constancia externa se registran después de verificar el resultado de emisión, sin reemplazar el identificador interno.

| Elemento | Tipo de modelado | Identidad o atributos principales | Dentro de qué frontera |
| --- | --- | --- | --- |
| CuentaPorCobrar | *Aggregate root* | cuentaId, pagadorId, período, total, saldo, estado, versión | CuentaPorCobrar |
| ConceptoCobro | *Value object* | origenId, tipo, descripción, cantidad, precio, descuento autorizado, neto y precioVersion | CuentaPorCobrar |
| AplicacionPago | *Value object* | origen, referencia, importeRecibido, importeAplicado, exceso, moneda y verificacionId | CuentaPorCobrar |
| DevolucionExceso | *Entity* interna | devolucionId, movimientoOrigen, monto, estado, aprobación y resultado | CuentaPorCobrar |
| Dinero | *Value object* | Importe decimal exacto y moneda | Ambas raíces financieras, como valores independientes |
| Factura | *Aggregate root* | facturaId, cuentaId, pagadorId, total, estado, referenciaFiscal y versión | Factura |
| LineaFactura | *Value object* | Descripción, origen, cantidad, precio y neto cobrados | Factura |
| DatosFacturacion | *Value object* | Titular, identificación tributaria y datos mínimos del documento | Factura |
| IntentoEmision | *Entity* interna | intentoId, ejecutor, estado, resultado y constancia de no emisión cuando aplica | Factura |
| ResultadoEmision | *Value object* | Referencia externa, importe, moneda, fecha y constancia verificadas | Factura |

**Figura 7.1a. Frontera de CuentaPorCobrar.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  subgraph C["<i>Aggregate</i> CuentaPorCobrar"]
    R["CuentaPorCobrar<br/>Aggregate root"] --> V["ConceptoCobro<br/>Value object · colección"]
    R --> P["AplicacionPago<br/>Value object · colección"]
    R --> D["DevolucionExceso<br/>Entity · colección"]
    R --> M["Dinero<br/>Value object"]
  end
```

**Figura 7.1b. Frontera de Factura.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  subgraph F["<i>Aggregate</i> Factura"]
    R["Factura<br/>Aggregate root"] --> L["LineaFactura<br/>Value object · colección"]
    R --> D["DatosFacturacion<br/>Value object"]
    R --> I["IntentoEmision<br/>Entity · historial"]
    R --> E["ResultadoEmision<br/>Value object"]
  end
```

### 7.2 Origen y composición de los cargos

La creación de una cuenta recibe conceptos ya valorizados y autorizados por sus responsables. CalculoDeCobro valida la moneda y verifica que cada neto corresponda a cantidad por precio unitario menos el descuento autorizado, antes de sumar los conceptos. CuentaPorCobrarFactory reúne ese resultado con el pagador, el vencimiento y la identidad del período u operación. Cada cantidad debe ser positiva y cada precio no negativo; el descuento se expresa como importe ya autorizado y no puede superar el bruto del concepto. Una cuenta creada mantiene sus conceptos fijos para que el pago y la factura describan exactamente la obligación aceptada.

| Concepto | Identidad mínima del origen | Cálculo y condición conservados | Momento de liquidación |
| --- | --- | --- | --- |
| Cuota del paquete | membresiaId, ciclo y paqueteId | Cuota y derechos del paquete vigente para ese ciclo | Mensual o al contratar pase por día |
| Hora de sala adicional | consumoId, reservaId y período | Cantidad que excede las horas incluidas; precio y descuento comprometidos | Cierre mensual o contratación puntual |
| Alquiler de sala | reservaId y tramo contratado | Franja, recurso y precio aceptado | Anticipado o cierre acordado |
| Cancelación tardía | reservaId y cancelacionId | Franja completa si se cancela con menos de cuatro horas de anticipación | Al confirmar la cancelación cobrable |
| Depósito de evento | eventoId y tramo Deposito | Importe anticipado exigido por la contratación | Antes de confirmar la condición económica del evento |
| Saldo de evento | eventoId y tramo Saldo | Total contratado menos depósito ya liquidado, más extras posteriores autorizados | Según vencimiento del remanente |
| Servicio adicional | servicioId, eventoId o reservaId | Descripción, cantidad, precio y autorización | En la cuenta que corresponda al consumo |

La identidad de cargo se forma con el origen del servicio, el tipo de concepto y el período o tramo. Esa combinación se reserva de forma única en el módulo al crear la cuenta. Una entrega repetida del mismo consumo no crea otro cargo ni cambia la cuenta existente. Un cargo adicional confirmado después de liquidar la cuenta se registra en otra cuenta, con identidad y relación al servicio original; no se reescriben los conceptos ya aceptados.

### 7.3 Invariantes de CuentaPorCobrar

| ID | Invariante | Condición de aceptación | Rechazo o consecuencia |
| --- | --- | --- | --- |
| I01 | Total positivo y saldo entre cero y total | Conceptos válidos y total igual a suma de netos | No crear neto cero, negativo o monto manual inconsistente |
| I02 | Misma moneda en conceptos, pagos y devoluciones | Movimiento y cuenta tienen la misma moneda | No aplicar conversión implícita |
| I03 | Solo movimiento verificado reduce saldo | Transacción externa y abono interno conciliados; importe recibido positivo | Comprobante aislado no modifica la obligación |
| I04 | Referencia aplicada una vez con datos originales | Repetición idéntica devuelve YaAplicado | Mismo origen y referencia con monto o cuenta distintos es conflicto |
| I05 | Pendiente equivale a saldo positivo; Saldada equivale a cero | Aplicación limitada al saldo restante | El exceso no vuelve negativo el saldo |
| I06 | Aplicaciones, excesos y eventos se guardan juntos | Movimiento nuevo aceptado en transacción local | Fallo de guardado no publica éxito |

Dinero utiliza precisión decimal fija y la escala definida para su moneda; las operaciones no emplean números binarios aproximados. El neto de cada concepto llega con el redondeo autorizado y el total suma esos netos sin recalcular el precio. Un descuento no puede superar el importe bruto ni producir un concepto negativo. La asociación de origen y referencia con una cuenta se protege además mediante unicidad transaccional del módulo. Una raíz aislada no conoce las aplicaciones guardadas en otra cuenta. Por eso el registro de la asociación y el guardado de la raíz ocurren en la misma transacción financiera. Un conflicto de concurrencia obliga a recargar antes de decidir si la entrega es repetida o incompatible.

### 7.4 Estados y operaciones económicas

La cuenta utiliza Pendiente y Saldada como estados económicos. Un pago parcial conserva Pendiente y reduce el saldo únicamente por el importe verificado. Un movimiento superior al saldo aplica la parte necesaria y registra el resto como ExcesoPago asociado a una DevolucionExceso. Un nuevo movimiento real después de saldar no se descarta como duplicado: queda completamente identificado como exceso, sin segunda transición a Saldada.

**Figura 7.4. Estados de CuentaPorCobrar.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
stateDiagram-v2
  direction TB
  [*] --> Pendiente: crear con total positivo
  Pendiente --> Pendiente: pago parcial verificado
  Pendiente --> Saldada: completar saldo
  Saldada --> Saldada: nuevo movimiento como exceso
```

| Estado | Operación | Guarda y resultado económico | Evento o auditoría |
| --- | --- | --- | --- |
| No existe | crearCuenta | Conceptos liquidados, identidad única, total positivo y moneda coherente | CuentaPorCobrarCreada |
| Pendiente | registrarPagoConfirmado | Verificación confiable; aplicar mínimo entre recibido y saldo | PagoRegistrado; CuentaSaldada solo si llega por primera vez a cero |
| Saldada | registrarPagoConfirmado con referencia nueva | Aplicación cero; conservar recibido como exceso | PagoRegistrado y ExcesoPagoIdentificado; no otra CuentaSaldada |
| Cualquiera | registrarPagoConfirmado con referencia idéntica | Comprobar datos originales antes de comparar con saldo actual | YaAplicado, sin eventos nuevos |
| Cualquiera | aprobarDevolucion | Exceso real verificado y autorización de Dirección | Aprobación registrada en entidad interna |
| Cualquiera | adquirirDevolucion | Estado aprobado y un único responsable de ejecución | EnProceso, guardado antes de enviar la devolución |
| Cualquiera | registrarNoEjecucionDevolucion | No salida, ausencia de instrucción en curso y cese reconocidos | Devuelve entidad a Aprobada para otra adquisición; conserva intento cerrado |
| Cualquiera | confirmarDevolucion | Referencia y abono de salida verificados; importe no mayor al exceso | DevolucionConfirmada; la cuenta no se reabre |

<a id="pseudocodigo-financiero"></a>

### 7.5 Pseudocódigo conceptual de CuentaPorCobrar

```text
registrarPagoConfirmado(pagoVerificado, ahora):
  exigir verificacion confiable para esta cuenta y su pagador
  referencia = pagoVerificado.origen y pagoVerificado.referencia
  importeRecibido = pagoVerificado.importe
  moneda = pagoVerificado.moneda
  si referencia ya aplicada:
    exigir mismo origen, importe recibido y moneda originales
    devolver YaAplicado sin cambiar saldo ni emitir eventos
  exigir importeRecibido > 0 y moneda igual a la cuenta
  importeAplicado = minimo(importeRecibido, saldo)
  exceso = importeRecibido - importeAplicado
  saldoAnterior = saldo
  conservar AplicacionPago con referencia, recibido, aplicado y exceso
  saldo = saldo - importeAplicado
  estado = Saldada si saldo = 0; de lo contrario Pendiente
  registrar PagoRegistrado con importes diferenciados
  si exceso > 0:
    crear DevolucionExceso Pendiente vinculada al movimiento
    registrar ExcesoPagoIdentificado
  si saldoAnterior > 0 y saldo = 0:
    registrar CuentaSaldada
  devolver Aplicado

aprobarDevolucion(devolucionId, autorizacion, ahora):
  exigir exceso identificado y autorizacion vigente de Direccion
  exigir que la suma devuelta y pendiente no supere el exceso original
  pasar devolucion a Aprobada y conservar autorizacion

adquirirDevolucion(devolucionId, ejecutorId, ahora):
  exigir devolucion Aprobada y ningun intento activo
  asignar ejecutor, crear intento y pasar a EnProceso
  devolver permiso provisional, valido solo despues de guardar y confirmar

registrarNoEjecucionDevolucion(devolucionId, evidencia, ahora):
  exigir no salida, no instruccion en curso y cese reconocido
  cerrar intento sin efecto y volver a Aprobada

registrarResultadoInciertoDevolucion(devolucionId, ahora):
  exigir intento adquirido que todavia no tenga resultado terminal
  conservar responsable e identidad; pasar devolucion a Incierta

confirmarDevolucion(devolucionId, resultadoVerificado, ahora):
  si resultado terminal identico: devolver SinEfecto
  exigir devolucion EnProceso o Incierta e intento adquirido por el ejecutor informado
  exigir referencia, destino, moneda e importe exacto de la devolucion verificados
  pasar a Devuelta y conservar evidencia
  registrar DevolucionConfirmada sin modificar saldo ni factura
```

RegistrarPagoService obtiene una verificación confiable y la entrega explícitamente como PagoVerificado a la raíz. El servicio valida el comprobante comunicado, la transacción del proveedor y el abono del registro de la empresa antes de invocar la raíz. Ninguna API permite que el cliente sustituya esa verificación por un campo booleano. La cuenta comprueba que la verificación corresponde a su identidad y pagador, protege sus invariantes y conserva los datos originales para reconocer la referencia incluso después de saldar.

### 7.6 Procedimiento de pago y devolución

El cliente comunica su comprobante a Recepción, que registra el pago informado para su revisión por Administración. El servicio de verificación consulta la transacción identificada y la compara con el registro del abono de Nodo. La conciliación exige origen, referencia, pagador, moneda e importe coherentes, y separa la fecha de comunicación de la fecha efectiva del abono. Una autorización de tarjeta sin captura y abono confirmados permanece en verificación y todavía no reduce el saldo.

Dirección autoriza la devolución sobre un exceso identificado y verificado. Administración conserva una identidad estable y adquiere la ejecución antes de solicitar la salida de dinero. Si se pierde la respuesta, la devolución queda Incierta y se consulta el movimiento antes de reenviar una instrucción. La confirmación de la salida cierra la devolución sin modificar el saldo saldado ni las líneas de una factura emitida.

### 7.7 Factura como parte del modelo financiero

La liquidación mensual establece lo que debe pagarse y puede existir antes de recibir dinero. Factura se prepara cuando la cuenta correspondiente queda saldada mediante movimientos verificados y los datos de facturación están completos. Esta separación permite cobrar cuotas y consumos del mes y, posteriormente, emitir el comprobante que documenta lo cobrado. Las operaciones anticipadas y los depósitos siguen el mismo procedimiento sobre su propia cuenta, con el detalle del tramo contratado.

| ID | Invariante de Factura | Garantía |
| --- | --- | --- |
| I13 | Total igual a suma de LineaFactura y al total aplicado de la cuenta saldada | No facturar el exceso ni omitir conceptos cobrados |
| I14 | Una factura ordinaria por cuenta y tipo de documento | Reentrega de CuentaSaldada no crea otro comprobante |
| I15 | Emitida exige referencia fiscal y resultado externo verificado | Solicitar emisión no equivale a emitir |
| I16 | Líneas emitidas inmutables y envío independiente | Cambio de tarifa o reenvío no modifica el documento |

**Figura 7.7. Estados de Factura.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
stateDiagram-v2
  direction TB
  [*] --> PendienteDeEmision: preparar desde cuenta saldada
  PendienteDeEmision --> Emitida: confirmar emisión
  Emitida --> Enviada: acreditar envío
```

| Operación de Factura | Entrada y guarda | Cambio permitido | Respuesta ante problema |
| --- | --- | --- | --- |
| prepararFactura | Cuenta saldada, conceptos cerrados, datos fiscales y clave de cuenta/documento no usada | Crear líneas, total y PendienteDeEmision | Cuenta aún pendiente o datos incompletos impiden preparar |
| adquirirEmision | PendienteDeEmision sin ejecución activa | Asignar intento y responsable antes de operar el portal | Otro ejecutor no recibe autorización |
| registrarNoEmision | Evidencia de ausencia de emisión, instrucción no vigente y cese reconocido | Cerrar intento sin efecto, conservar PendienteDeEmision | No se vuelve a adquirir por mera expiración |
| confirmarEmision | Resultado de la emisión adquirida, referencia y total coincidentes | Emitida y FacturaEmitida | Incertidumbre conserva pendiente y exige consulta externa |
| registrarFalloEnvio | Emisión existente y resultado de envío fallido | Conservar el estado previo, Emitida o Enviada, y el detalle del intento | No volver a emitir factura |
| confirmarEnvio | Constancia de envío del documento correcto | Enviada y FacturaEnviada | Mensaje ajeno o referencia distinta se rechazan |

```text
Factura.preparar(cuentaSaldada, datosFacturacion, identidadDocumento):
  exigir saldo = 0 y total aplicado a conceptos = total de cuenta
  copiar LineaFactura y condiciones economicas cerradas
  exigir total = suma de lineas y moneda coherente
  crear PendienteDeEmision y registrar FacturaPreparada

adquirirEmision(ejecutorId, ahora):
  exigir PendienteDeEmision y ningun intento activo o incierto
  crear IntentoEmision con responsable unico
  devolver permiso provisional, valido solo despues de guardar y confirmar

registrarNoEmision(evidencia, ahora):
  exigir no documento emitido, no instruccion vigente y cese reconocido
  cerrar intento sin efecto; conservar PendienteDeEmision

registrarResultadoInciertoEmision(intentoId, ahora):
  exigir intento adquirido sin resultado terminal
  conservar intento y responsable; mantener PendienteDeEmision

confirmarEmision(resultado, ahora):
  si mismo resultado ya confirmado: devolver SinEfecto
  exigir intento adquirido, referencia fiscal verificable e importes coincidentes
  conservar ResultadoEmision y pasar a Emitida
  registrar FacturaEmitida

confirmarEnvio(constancia, ahora):
  exigir documento Emitida o Enviada y constancia correspondiente
  si envio identico ya registrado: devolver SinEfecto
  conservar constancia y pasar a Enviada
  registrar FacturaEnviada
```

La creación de Factura no contiene una llamada al proveedor de pagos ni al portal de emisión. La aplicación reúne la cuenta saldada y la evidencia, y la raíz valida que el documento interno pueda nacer. Administración actúa como responsable de emisión y registra el resultado externo de su intento adquirido. Si la respuesta se pierde, primero consulta la referencia del documento para evitar emitir otro comprobante por la misma cuenta.

### 7.8 Eventos y trazabilidad económica

| Evento | Raíz responsable | Información mínima de negocio |
| --- | --- | --- |
| CuentaPorCobrarCreada | CuentaPorCobrar | Cuenta, pagador, período, total, moneda y vencimiento |
| PagoRegistrado | CuentaPorCobrar | Referencia, recibido, aplicado, exceso, saldo restante y verificación |
| CuentaSaldada | CuentaPorCobrar | Cuenta, pagador, versión y momento de saldo cero |
| ExcesoPagoIdentificado | CuentaPorCobrar | Cuenta, movimiento, devolución y exceso |
| DevolucionConfirmada | CuentaPorCobrar | Devolución, importe y referencia externa de salida |
| FacturaPreparada | Factura | Factura, cuenta, conceptos copiados, total y moneda |
| FacturaEmitida | Factura | Factura, referencia fiscal y fecha de emisión |
| FacturaEnviada | Factura | Factura, envío, canal y constancia |

Cada evento conserva eventoId, tipo, versionContrato, identidad y versión del *aggregate*, instante de ocurrencia y correlationId del comando. El adaptador conserva eventoId al construir el mensaje de integración, aunque el transporte asigne además su propio messageId. La confirmación de persistencia precede a la publicación de integración. La entrega de un evento puede repetirse y el consumidor debe reconocer su identidad antes de reaccionar. Ningún mensaje financiero contiene una instrucción de abrir la puerta ni datos biométricos del miembro.

---

<a id="capitulo-08"></a>

## 8. Modelo de dominio de Control de Acceso

### 8.1 Raíz, entidad interna y referencias

AccesoMiembro es la *aggregate root* del permiso operativo de un miembro. Su identidad permanece aunque se suspenda el ingreso, cambie el plan o se vuelva a registrar la huella. Contiene el estado, la observación de la cerradura, el historial de operaciones y las incidencias que condicionan nuevas actuaciones. Se guarda como una unidad de consistencia y no contiene la cuenta financiera, la membresía completa ni la plantilla biométrica.

OperacionRegistroHuella es una *entity* interna porque conserva identidad y continuidad durante solicitudes, intentos y confirmaciones. EvidenciaFisica, EvaluacionAcceso, ConstanciaAviso y ObservacionRegistroHuella son *value objects* que describen datos inmutables utilizados por la raíz. IntentoOperacion es una *entity* interna de OperacionRegistroHuella y conserva el ejecutor autorizado, la adquisición y el resultado acreditado. La consulta económica se traduce a SituacionPago mediante una *Anti-Corruption Layer* y cubre las obligaciones del pagador que afectan al derecho de ese miembro.

| Componente | Identidad o atributos | Comportamiento protegido | Referencias externas |
| --- | --- | --- | --- |
| AccesoMiembro, *aggregate root* | accesoId, miembroId, pagadorId, planId, estado, versión y política | Solicitar, confirmar, cancelar y reconciliar operaciones | Cuenta y obligación por identificador; plan por versión |
| OperacionRegistroHuella, *entity* interna | operacionId, tipo, estado, parentOperacionId e intentos | Un único ejecutor adquirido y cierre explícito | dispositivoId y ejecutorId |
| IntentoOperacion, *entity* interna de OperacionRegistroHuella | intentoId, ejecutor, adquisición y resultado | No reasignar mientras el ejecutor anterior puede actuar | Usuario autorizado y dispositivo |
| EvidenciaFisica, *value object* | Tipo, operación, intento, responsable y tiempos | Acreditar el efecto concreto o el cese sin efecto | Referencia protegida de la constancia |
| EvaluacionAcceso, *value object* | Acción, sujeto, pagador, versiones, motivos y momento | Una evaluación válida para la acción y versión actuales | Versiones de plan y situación financiera |
| ObservacionRegistroHuella, *value object* | Presente, Ausente o Desconocida y fecha | Separar estado histórico de condición física conocida | Dispositivo observado |

**Figura 8.1a. Operaciones dentro de AccesoMiembro.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  subgraph A["<i>Aggregate</i> AccesoMiembro"]
    direction TB
    R["AccesoMiembro<br/>Aggregate root"] --> O["OperacionRegistroHuella<br/>Entity · historial"]
    O --> I["IntentoOperacion<br/>Entity · historial"]
    I --> E["EvidenciaFisica<br/>Value object"]
  end
```

**Figura 8.1b. Valores de decisión de AccesoMiembro.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  subgraph A["<i>Aggregate</i> AccesoMiembro"]
    direction LR
    R["AccesoMiembro<br/>Aggregate root"]
    R --> V["SituacionPago y EvaluacionAcceso<br/>Value objects"]
    R --> P["ConstanciaAviso y PeriodoTolerancia<br/>Value objects"]
    R --> F["Estado, observación e incidencia<br/>Valores del permiso"]
  end
```

Las figuras 8.1a y 8.1b muestran dos vistas del mismo *aggregate*, con sus operaciones y sus valores de decisión. Ambas se guardan mediante la raíz AccesoMiembro y no representan fronteras independientes. El mecanismo de ingreso utiliza una cerradura con lector de huella. Recepción da de baja el registro para suspender y vuelve a registrarlo para restablecer. Alta y Retiro nombran actuaciones sobre el registro del dispositivo, mientras el sistema conserva únicamente su identificación administrativa. La información biométrica permanece fuera del dominio y no se incluye en mensajes, vistas ni constancias del sistema.

La situación financiera comprende cuotas, consumos y otros cargos que afecten al derecho del miembro. Una deuda de un evento ajeno no se convierte automáticamente en deuda bloqueante de una membresía. En los planes de equipo se identifica el titular pagador y la lista de derechos individuales cubiertos por ese plan. La evaluación rechaza alcances incompletos para evitar que saldar una sola cuenta se interprete como regularización de todas las obligaciones exigibles.

### 8.2 Invariantes y política temporal

| ID | Invariante | Ejemplo o rechazo |
| --- | --- | --- |
| I07 | La suspensión por impago solo se solicita desde Habilitado cuando existe obligación bloqueante vencida, plazo de cinco días y aviso de veinticuatro horas cumplidos | Antes del quinto día, aviso insuficiente, alcance incompleto o ausencia de deuda vencida fuera de tolerancia rechazan |
| I08 | Suspendido exige evidencia válida de registro de huella dado de baja/ausente: confirmación ordinaria, retiro correctivo o reconciliación física expresa | Orden enviada, *timeout* o alta fallida no acreditan retiro; la corrección exige otra operación identificada |
| I09 | Solicitar alta ordinaria exige Suspendido, obligaciones exigibles regularizadas, plan vigente y permiso explícito; habilitar exige alta observada y reevaluación de rehabilitación favorable | Pago sin vigencia rechaza; resultado incierto nunca habilita |
| I10 | Como máximo una operación no terminal por acceso; solo un ejecutor/intento adquirido y persistido puede actuar | Dos ejecutores de la misma versión no reciben ambos autorización |
| I11 | Reintentar reutiliza operacionId con intento nuevo y evaluación reciente; terminales idénticos no mutan ni reemiten eventos | No reenviar tras *timeout*, no liberar por mero vencimiento de un bloqueo |
| I12 | Referencias externas por ID y ausencia de biometría en dominio, ejemplos y eventos | Evidencia no contiene plantilla o foto de huella |

Mantengo una tolerancia de cinco días calendario desde el vencimiento de cada obligación que puede bloquear el derecho de acceso. Defino además un aviso exitoso con al menos veinticuatro horas de anticipación. La fecha efectiva de corte es el máximo entre vencimiento más cinco días y envío acreditado más veinticuatro horas. Esta regla permite avisar tarde sin retrofechar el mensaje ni ejecutar una suspensión que carezca del margen previo.

| Dato o situación | Regla operativa | Resultado |
| --- | --- | --- |
| Obligación vence el 1 de noviembre a las 09:00 | Límite económico: 6 de noviembre a las 09:00, America/La_Paz | Antes del límite no se solicita suspensión por esa obligación |
| Aviso enviado el 2 de noviembre a las 10:00 | Margen de aviso termina el 3 de noviembre a las 10:00 | Corte posible desde el 6 de noviembre a las 09:00 si persiste deuda |
| Aviso enviado el 6 de noviembre a las 12:00 | El margen termina el 7 de noviembre a las 12:00 | El corte espera hasta ese instante |
| Plan de horario normal | Lunes a viernes, intervalo 08:00 a 18:00, fin excluido | Un intento a las 18:00 queda fuera de horario |
| Plan con acceso continuo | Todos los días, dentro de la vigencia contratada | El horario no restringe, pero deuda y vigencia siguen evaluándose |
| Pago y alta a las 20:00 | Se permite configurar si corresponde rehabilitar | El intento de ingreso depende del horario del plan |

Para el horario normal establezco como configuración de operación la franja de lunes a viernes de 08:00 a 18:00, en America/La_Paz. Cada derecho conserva el horario y la versión que le corresponden, por lo que un plan continuo no hereda esa restricción. Rehabilitar el registro e ingresar son acciones distintas. Administración solicita la rehabilitación con obligaciones exigibles regularizadas y derecho vigente, bajo la política de Dirección. Cada entrada agrega la restricción horaria y la ausencia de incidencias que impidan confiar en el estado. El adaptador de apertura debe aplicar esa evaluación; configurar una huella no puede presentarse como autorización de ingreso fuera del horario contratado.

### 8.3 Ciclo de OperacionRegistroHuella y exclusión previa al efecto

Los tipos de operación son RetiroImpago, Alta y RetiroCorrectivo. Cada operación conserva su parentOperacionId si corrige otra y un historial de intentos. Confirmada y Cancelada son terminales; Solicitada, EnEjecucion y ResultadoIncierto son no terminales. Conservar historia permite reconocer duplicados después de cerrar la operación. Confirmar o cancelar libera el lugar de operación vigente; una operación terminal no autoriza nuevos efectos con esa identidad.

| Origen de operación | Método de raíz / guarda | Destino y significado |
| --- | --- | --- |
| No existe | Solicitud ordinaria/correctiva válida; no hay otra no terminal | Solicitada, sin permiso físico todavía |
| Solicitada | adquirirEjecucion(operacionId, ejecutorId, ahora), guardar con versionEsperada y confirmar *commit* | EnEjecucion con intentoId nuevo; solo el ganador recibe autorización para actuar |
| EnEjecucion | Evidencia positiva del intento, operación/tipo/ejecutor/dispositivo correctos | Confirmada; aplicar transición de acceso y conservar resultado original |
| EnEjecucion o ResultadoIncierto | registrarResultadoIncierto(operacionId, ahora) o sin evidencia de no efecto | ResultadoIncierto; no entregar otra orden ni liberar ejecutor automáticamente |
| EnEjecucion o ResultadoIncierto | registrarNoEjecucion(evidencia, ahora): operador acredita no efecto, ausencia de actuación en curso y cese de autorización anterior | Solicitada; intento anterior cerrado sin efecto, elegibilidad se reevalúa antes de adquirir otro |
| Solicitada | Cancelación con evaluación que invalida motivo y evidencia de no efecto/no intento activo | Cancelada; transición de acceso correspondiente, sin evento de éxito físico |
| Confirmada o Cancelada | Resultado idéntico al conservado | SinEfecto; no nuevo evento ni actuación |
| Terminal o intento antiguo | Evidencia contradictoria de acto real | registrarEvidenciaContradictoria(evidencia, ahora) conserva historial, abre incidencia y congela; luego reconciliarResultadoFisico |

Confirmada acredita el efecto de un intento identificado. Una cancelación ordinaria acredita que no hubo efecto; excepcionalmente, reconciliarResultadoFisico puede cerrar una operación irresoluble como Cancelada con motivo ReconciliacionSinAtribucion, después de cese e inspección actual. Esa variante conserva el resultado histórico Desconocido y no afirma que nunca hubo efecto. Si existe evidencia atribuible al intento, la operación se cierra como Confirmada. El cierre cancela cualquier autorización anterior y conserva por separado lo que se conoce del resultado histórico y lo que acredita la inspección actual.

La adquisición se guarda atómicamente **antes** de entregar la tarea física, usando la versión de AccesoMiembro. El segundo escritor recarga y no actúa. La asignación y aceptación manuales deben respetar esa exclusión; una asignación que solo exista en memoria no protege frente a otro proceso ni sobrevive a una caída. La exclusión entre procesos requiere almacenamiento compartido y el mismo protocolo de adquisición.

Una caída después de adquirir, incluso antes de saber si el operador recibió la tarea, conserva ejecución/resultado incierto. No hay reasignación por *timeout* ni *lease* que «venza» mientras el operador antiguo puede actuar. Se exige cierre/revocación reconocida de su autorización y evidencia manual de no ejecución o resultado real antes de reasignar. Si no se obtiene esa evidencia, la operación continúa en revisión y conserva a su responsable. El contrato de integración exige conservar esa exclusión también en la coordinación humana.

### 8.4 Estado de acceso y tabla de transiciones

**Figura 8.4. Transiciones ordinarias de AccesoMiembro.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
stateDiagram-v2
  direction TB
  [*] --> Habilitado: alta inicial acreditada
  Habilitado --> SuspensionPendiente: solicitar suspensión
  SuspensionPendiente --> Suspendido: acreditar retiro
  SuspensionPendiente --> Habilitado: cancelar sin efecto
  Suspendido --> RestablecimientoPendiente: solicitar alta
  RestablecimientoPendiente --> Habilitado: alta real y elegible
  RestablecimientoPendiente --> Suspendido: cancelar o corregir
```

El diagrama muestra las transiciones ordinarias entre los cuatro estados de acceso. Los fallos conocidos y los resultados inciertos conservan el estado pendiente, y las reconciliaciones excepcionales se detallan en la tabla. En RestablecimientoPendiente la observación puede ser Presente con incidencia: por ello la vista debe mostrar estado, operación, observación y revisión, sin afirmar que la puerta está bloqueada. Habilitado es disponibilidad operativa del registro de huella con derechos y cada ingreso sigue sujeto al horario. La evaluación de entrada exige además una observación confiable sin congelación.

| Origen | Método | Guarda / dueño de decisión | Destino, operación y evento |
| --- | --- | --- | --- |
| Habilitado | solicitarSuspension(evaluacion, ahora) | Raíz valida evaluación Suspender favorable, contexto y ausencia de operación; evaluador ya comprobó política I07 | SuspensionPendiente; RetiroImpago Solicitada; SuspensionSolicitada |
| SuspensionPendiente | confirmarSuspension(evidencia, ahora) | Retiro real acreditado del intento adquirido | Suspendido y observación Ausente; operación Confirmada; AccesoSuspendido, aun si se pagó durante retirada |
| SuspensionPendiente | cancelarSuspensionNoEjecutada(evaluacion, evidencia, ahora) | Evaluación Suspender actual desfavorable porque ya no existe motivo vigente para la suspensión; no efecto ni intento activo | Habilitado, operación Cancelada; auditoría, sin AccesoRestablecido por alta inexistente |
| Suspendido | solicitarRestablecimiento(evaluacion, autoridad, ahora) | Evaluación Restablecer favorable y permiso de solicitud de Administración; sin operación no terminal | RestablecimientoPendiente, Alta Solicitada; RestablecimientoSolicitado |
| RestablecimientoPendiente | confirmarRestablecimiento(evidencia, evaluacion, ahora) | Alta real y evaluación Restablecer actual favorable | Habilitado/Presente; Alta Confirmada; AccesoRestablecido |
| RestablecimientoPendiente | cancelarRestablecimientoNoEjecutado(evaluacion, evidencia, ahora) | Evaluación Restablecer desfavorable; no efecto y ningún intento activo | Suspendido/Ausente; Alta Cancelada; solo auditoría |
| RestablecimientoPendiente | registrarAltaNoElegible(evidencia, evaluacion, ahora) | Alta real pero evaluación Restablecer desfavorable o NoEvaluable | Mantiene pendiente, observación Presente e incidencia; Alta Confirmada y cerrada; no AccesoRestablecido |
| RestablecimientoPendiente con incidencia | solicitarRetiroCorrectivo(autoridad, ahora) | Alta no elegible registrada; permiso de corrección de Recepción; no otra operación ni ejecutor activo | RetiroCorrectivo Solicitada con parentOperacionId; mantiene pendiente, auditoría; no se inventa deuda ni SuspensionSolicitada por impago |
| RestablecimientoPendiente con correctiva | completarRetiroCorrectivo(evidencia, ahora) | Retiro confirmado del intento correctivo adquirido | Suspendido/Ausente, correctiva Confirmada, incidencia resuelta; AccesoSuspendido con motivo CorreccionElegibilidad |
| Cualquiera, con evidencia de terminal/intento antiguo incompatible | registrarEvidenciaContradictoria(evidencia, ahora) | Evidencia correlacionada auténtica que contradice resultado/historial; no simple mensaje mal formado | Conserva evidencia previa y nueva, abre incidencia y congela despachos; observación actual Desconocida, conserva última observación en historial; no cambia estado principal ni emite éxito |
| Cualquiera, con despachos congelados | reconciliarResultadoFisico(evidenciaInspeccion, evaluacion, autoridad, ahora) | Inspección actual posterior a cese confirmado de ejecutores; evidencia previa contradictoria conservada; sin actuación en curso | Ausente: Suspendido; Presente y Restablecer favorable: Habilitado; Presente no elegible: RestablecimientoPendiente con incidencia y retiro correctivo. Cierra Confirmada solo con evidencia atribuible al intento; si irresoluble, Cancelada por ReconciliacionSinAtribucion conservando resultado histórico Desconocido |

La reconciliación requiere una inspección actual y el cese confirmado de los ejecutores anteriores. AccesoSuspendido o AccesoRestablecido se emiten cuando la evidencia acredita el resultado correspondiente y cambia el estado final. Al resolver la discrepancia, se cierran las autorizaciones anteriores y se levanta la congelación de nuevos despachos. Si el registro está Presente pero el miembro no es elegible, permanece la incidencia y solo se permite adquirir la baja correctiva.

Si no puede acreditarse la condición física o el cese de los operadores, la incidencia y la congelación permanecen abiertas. El estado principal conserva el último resultado confirmado y la observación Desconocida indica que la condición física actual todavía requiere verificación. Esa incidencia impide una evaluación favorable de ingreso aunque el estado histórico sea Habilitado. Las transiciones se realizan mediante AccesoMiembro y ningún controlador escribe directamente su estado.

<a id="contratos-raiz-acceso"></a>

### 8.5 Contratos conceptuales de la raíz

EvaluacionAcceso es un *value object* generado únicamente por EvaluadorDeIngreso del dominio de Acceso. Contiene evaluacionId, acción Suspender/Restablecer/Ingresar, resultado, motivos, miembroId, pagadorId, obligacionesVersion, accesoVersion, versiones/fechas de insumos, politicaVersion y evaluadoEn. El contexto conserva además la observación, la incidencia bloqueante y la congelación de despachos para impedir ingreso con estado histórico no confiable. La aplicación no puede convertir un booleano del cliente en evaluación. La raíz valida procedencia del dominio, acción, sujeto, pagador, alcance de obligaciones, versión de acceso, política y contexto temporal de su invocación; un conflicto obliga a recargar y reevaluar. La consulta debe haberse obtenido en la fase actual y no superar treinta segundos al decidir; consultar de nuevo no elimina la posibilidad de una carrera externa.

```text
solicitarSuspension(evaluacion, ahora):
  exigir Habilitado y ninguna operación no terminal
  validarContextoEvaluacion(evaluacion, Suspender, ahora)
  exigir resultado favorable
  crear RetiroImpago Solicitada y pasar a SuspensionPendiente
  registrar SuspensionSolicitada; ningún permiso físico antes de adquirir

solicitarRestablecimiento(evaluacion, autoridad, ahora):
  exigir Suspendido y ninguna operación no terminal
  validarContextoEvaluacion(evaluacion, Restablecer, ahora)
  exigir favorable y autoridad válida para la operación
  crear Alta Solicitada y pasar a RestablecimientoPendiente
  registrar RestablecimientoSolicitado con evaluacionId

adquirirEjecucion(operacionId, ejecutorId, ahora):
  exigir operación Solicitada, sin incidencia que congele despachos
  crear intentoId; asignar ejecutor único; pasar operación a EnEjecucion
  devolver adquisición provisional: solo autoriza actuar tras guardar con versión y commit

confirmarSuspension(evidencia, ahora):
  si resultado terminal idéntico: SinEfecto
  exigir retiro real, operación RetiroImpago e intento/ejecutor correlacionados
  cerrar operación Confirmada; registrar observación Ausente
  pasar a Suspendido; registrar AccesoSuspendido

confirmarRestablecimiento(evidencia, evaluacion, ahora):
  si resultado terminal idéntico: SinEfecto
  exigir Alta real del intento adquirido
  validarContextoEvaluacion(evaluacion, Restablecer, ahora)
  exigir evaluación actual favorable
  cerrar Alta Confirmada; observar Presente; pasar a Habilitado
  registrar AccesoRestablecido

registrarAltaNoElegible(evidencia, evaluacion, ahora):
  exigir Alta real y evaluación Restablecer actual desfavorable o NoEvaluable
  cerrar Alta Confirmada conservando hecho, observar Presente e incidencia
  mantener RestablecimientoPendiente; no emitir AccesoRestablecido

solicitarRetiroCorrectivo(autoridad, ahora):
  exigir incidencia de presencia no elegible, cese del alta y ninguna operación vigente
  crear RetiroCorrectivo Solicitada con parentOperacionId y autoridad de corrección
  mantener RestablecimientoPendiente; auditar solicitud correctiva

completarRetiroCorrectivo(evidencia, ahora):
  exigir retiro real del intento correctivo adquirido
  cerrar Confirmada; observar Ausente, resolver incidencia y pasar a Suspendido
  registrar AccesoSuspendido con motivo CorreccionElegibilidad
```

registrarEvidenciaContradictoria conserva evidencia y congela mediante la raíz. reconciliarResultadoFisico normaliza únicamente después del cese y la inspección requeridos por la tabla. No se oculta ni elimina la evidencia contradictoria. La clausura del intento anterior no concede permiso a su ejecutor para volver a actuar.

Los métodos registrarAviso(constancia, ahora), registrarNoEjecucion(evidencia, ahora), registrarResultadoIncierto(operacionId, ahora), las dos cancelaciones y reconciliarResultadoFisico aplican las condiciones y los efectos definidos en las tablas 8.3 y 8.4. registrarAviso comprueba envío exitoso, sujeto, obligaciones y tiempo; no envía mensajes desde la raíz. Todas las mutaciones guardan estado, operación, historial, observación, evidencia, versión y eventos pendientes en una transacción local. Un fallo de guardado conserva el resultado externo por recuperar y no ordena repetirlo.

### 8.6 Carreras, inspección y límites

Si el pago llega antes del retiro, el ejecutor que ganó adquisición revalida deuda; si aún no actuó, acredita no ejecución/cese y cancela por raíz. Si el pago llega durante retirada y el retiro ocurrió, se confirma el hecho y se inicia evaluación de rehabilitación en otra transacción. Si un alta ocurrió y cambió la elegibilidad, registrarAltaNoElegible cierra esa operación sin habilitar. La correctiva tiene otro identificador y no se ejecuta hasta adquirirla.

La confirmación idéntica de operación terminal es idempotente aun si hay operaciones posteriores. Una confirmación incompatible no reabre el terminal ni vuelve a actuar: la raíz registra esa evidencia, abre incidencia y congela despachos mediante registrarEvidenciaContradictoria. Recepción confirma cese de operadores e inspecciona el estado actual antes de reconciliarResultadoFisico. Solo la inspección actual permite elegir el resultado de la transición de reconciliación; un mensaje viejo no sustituye observación actual. Si no se puede acreditar el cese e inspeccionar, el caso sigue pendiente con responsable y sin reintento automático.

Estas reglas reducen órdenes duplicadas dentro de un protocolo compartido; no vuelven idempotente el hardware ni prometen una transacción global con el pago y el plan. Dirección establece la política y autoriza el alta inicial del miembro. Administración solicita las actuaciones ordinarias y Recepción tiene permiso para ejecutar, confirmar y corregir el registro de huella. Las excepciones requieren una autorización explícita de Dirección y no alteran el registro financiero. La identidad y el alcance del permiso se conservan en cada operación.

### 8.7 Eventos y evidencias

| Evento | Cuándo se produce | Datos mínimos además de los metadatos comunes |
| --- | --- | --- |
| SuspensionSolicitada | Solicitud ordinaria de impago admitida | accesoId, miembroId, pagadorId, obligacionesId, operacionId, evaluacionId, motivo, solicitadoEn |
| AccesoSuspendido | Retiro ordinario confirmado; también correctivo o inspección excepcional acreditada con cambio a Suspendido | accesoId, miembroId, operacionId o reconciliacionId, evidenciaId, motivo, ocurridoEn, confirmadoEn |
| RestablecimientoSolicitado | Solicitud ordinaria de alta elegible | accesoId, miembroId, operacionId, evaluacionId, solicitadoEn |
| AccesoRestablecido | Alta confirmada y elegible; inspección excepcional Presente elegible que cambia a Habilitado | accesoId, miembroId, operacionId o reconciliacionId, evaluacionId, evidenciaId, ocurridoEn, confirmadoEn |

Cada mensaje diferencia el instante del acto o la inspección, el de recepción y el de confirmación de la transacción. La evidencia identifica tipo, dispositivo, operación, intento, ejecutor y resultado; una inspección usa reconciliacionId y constancia de cese. Las adquisiciones, cancelaciones e incidencias se conservan en la auditoría transaccional de AccesoMiembro. Cada publicación ocurre después de confirmar el guardado local.

---

<a id="capitulo-09"></a>

## 9. Componentes y responsabilidades del modelo

### 9.1 Distribución del comportamiento

Las reglas que dependen del estado de una raíz permanecen dentro de esa raíz. CuentaPorCobrar modifica saldo y aplicaciones, Factura protege líneas y emisión, y AccesoMiembro controla operaciones de huella. Uso *Domain Services* para los cálculos y políticas que combinan valores y no pertenecen naturalmente a una sola entidad. La aplicación obtiene información externa, inicia las transacciones y decide cuándo volver a consultar, sin sustituir las decisiones del dominio.

| Responsabilidad | Componente | Justificación |
| --- | --- | --- |
| Saldo, aplicaciones y excesos | CuentaPorCobrar | Los datos deben cambiar juntos para conservar la igualdad económica |
| Total documental y estado fiscal | Factura | Las líneas y su confirmación de emisión forman una unidad |
| Derecho operativo y evidencia de huella | AccesoMiembro | La confirmación cierra el intento y cambia el estado en una transacción |
| Sumar conceptos y descuentos autorizados | CalculoDeCobro | Combina valores de servicios distintos sin consultar almacenamiento |
| Contrastar comprobante, transacción y abono | ConciliadorDePago | Produce una conclusión identificada a partir de evidencia ya obtenida |
| Evaluar suspensión, rehabilitación e ingreso | EvaluadorDeIngreso | Combina derecho, situación económica, tiempo y política |
| Consultar proveedor y persistir | *Application Service* y adaptadores | Son coordinación y efectos externos, no invariantes de una entidad |

### 9.2 Contratos de *Domain Services*

| Servicio | Firma conceptual | Resultado y reglas |
| --- | --- | --- |
| CalculoDeCobro | calcular(conceptos, moneda, autorizaciones) | Desglose y neto; exige mismo tipo monetario, origen y descuento autorizado |
| ConciliadorDePago | evaluar(comprobanteOpcional, transaccion, abono, cuentaObjetivo) | PagoVerificado o discrepancia; contrasta origen, titular, referencia, importe y moneda |
| EvaluadorDeIngreso | evaluarSuspension(contexto, situacion, avisos, ahora) | EvaluacionAcceso para Suspender; obligación relevante, cinco días y aviso de veinticuatro horas |
| EvaluadorDeIngreso | evaluarRestablecimiento(contexto, situacion, plan, politica, ahora) | EvaluacionAcceso para Restablecer; obligaciones exigibles regularizadas y derecho vigente |
| EvaluadorDeIngreso | evaluarIngreso(contexto, situacion, plan, ahora) | EvaluacionAcceso para Ingresar de un miembro; derecho vigente, horario permitido, estado confiable y política económica |
| EvaluadorDeIngreso | evaluarDerechoTemporal(derecho, ahora) | Resultado de ingreso temporal; sujeto, origen, intervalo y condiciones confirmadas por Reservas o Agenda de eventos |

Los servicios son puros y no consultan APIs ni guardan objetos. La aplicación les entrega valores que ya obtuvo de los puertos correspondientes. ConciliadorDePago no acepta un campo de confirmación declarado por el cliente y no confunde autorización de tarjeta con abono. EvaluadorDeIngreso produce una evaluación para una acción concreta y la raíz comprueba que todavía corresponde a su identidad, versión y política.

El contexto de acceso de un miembro contiene accesoId, miembroId, pagadorId, estado, observación, incidencia, accesoVersion y politicaVersion. SituacionPago conserva el alcance de obligaciones, sus vencimientos y versiones, y la fecha de consulta. Los avisos identifican las obligaciones comunicadas y la fecha efectiva de envío, de modo que el servicio puede calcular el corte aplicable. La evaluación conserva esos datos de contexto, sus motivos y su momento, con resultados Favorable, Desfavorable o NoEvaluable.

La rehabilitación permite configurar fuera del horario de entrada si las condiciones económicas y de vigencia están satisfechas. La evaluación de ingreso agrega el horario y la ausencia de una incidencia bloqueante. Durante la tolerancia se puede mantener el derecho del miembro aunque exista una obligación pendiente, siempre que no haya llegado una condición de corte aplicable. Una situación incompleta o fuera de la antigüedad permitida se trata como NoEvaluable y requiere nueva consulta.

Los derechos temporales se evalúan sin fabricar un AccesoMiembro para el visitante. El contrato del subdominio responsable confirma la contratación y las condiciones económicas del derecho identificado. El servicio comprueba sujeto, origen, vigencia, restricciones y actualidad de esa confirmación. Recepción puede autorizar una apertura supervisada bajo ese resultado y conserva un registro de actuación, sin alta biométrica del visitante ni otro repositorio de membresía.

### 9.3 *Repositories* por *aggregate root*

```text
CuentaPorCobrarRepository
  obtener(cuentaId) -> raiz y version, o Ausente
  guardar(cuenta, versionEsperada) -> nueva version, o Conflicto

FacturaRepository
  obtener(facturaId) -> raiz y version, o Ausente
  guardar(factura, versionEsperada) -> nueva version, o Conflicto

AccesoMiembroRepository
  obtener(accesoId) -> raiz y version, o Ausente
  guardar(acceso, versionEsperada) -> nueva version, o Conflicto
```

Defino tres interfaces de *Repository*, una por *aggregate root*, para recuperar y guardar cada frontera completa. Las entidades internas se guardan a través de su raíz y no necesitan un repositorio propio. La carga entrega una instancia rehidratada independiente del estado persistido, y una mutación que todavía no se confirmó no cambia el almacenamiento compartido. Guardar verifica la versión y conserva todos los componentes internos y eventos pendientes en una transacción local.

El módulo financiero coordina las restricciones de origen del cargo, referencia del movimiento y factura por cuenta. Esas claves se reservan atómicamente junto con la raíz que corresponda. La consulta pública de situación económica no concede acceso al repositorio financiero desde Control de Acceso. Si existe un conflicto de versión, la aplicación recarga y vuelve a evaluar antes de decidir si falta una actuación o si el resultado ya se produjo.

### 9.4 Creación de objetos y *Factory*

CuentaPorCobrarFactory aporta valor al reunir conceptos valorizados, pagador, moneda, período y vencimiento. Invoca CalculoDeCobro y construye una obligación válida sin emitir facturas ni confirmar pagos. Factura.preparar copia el detalle de la cuenta saldada y valida la coherencia del documento dentro de la propia raíz. AccesoMiembro.registrar conserva identidad y evidencia del alta inicial sin añadir una *Factory* que solo duplique esa creación.

La rehidratación se diferencia de la creación de negocio. Volver a cargar una cuenta no registra otro evento de creación y no vuelve a liquidar el servicio. Recuperar Factura conserva la referencia fiscal y las líneas originales aunque la tarifa del servicio ya haya cambiado. Recuperar AccesoMiembro conserva operaciones terminales para reconocer confirmaciones antiguas sin ejecutar otra vez una actuación.

### 9.5 Puertos de integración

| Puerto | Operación conceptual | Resultado o error significativo |
| --- | --- | --- |
| CargosServiciosPort | Obtener conceptos confirmados de paquetes, reservas y eventos | Conceptos identificados; origen incompleto no se liquida |
| VerificacionPagoPort | Consultar transacción externa por referencia | Estado, importe, moneda, titular y datos verificables |
| RegistroAbonosPort | Consultar abono empresarial correspondiente | Abono confirmado, pendiente o discrepancia |
| DevolucionPort | Ejecutar o consultar devolución con identidad estable | Salida confirmada, no ejecutada o incierta |
| EmisionFacturaPort | Operar o registrar emisión y consultar resultado | Referencia fiscal confirmada, fallo o incertidumbre |
| SituacionFinancieraPort | Consultar obligaciones del pagador y alcance | Situación completa, fecha y versiones |
| MembresiaPort | Consultar derecho, beneficiarios, vigencia y horario | Derecho identificado y versionado |
| OperacionHuellaPort | Entregar tarea adquirida y consultar efecto o cese | Evidencia de alta, baja, no ejecución o incertidumbre |
| AvisosPort | Solicitar envío y consultar resultado | Enviado, Fallido o Incierto con identidad correlacionada |
| Reloj | Obtener instante de la fase actual | Fecha con zona y calendario de política |

### 9.6 *Application Services*

| Caso | Servicio | Coordinación principal |
| --- | --- | --- |
| CU01 | LiquidarCuentaService | Obtener cargos, crear cuenta y reservar orígenes |
| CU02 | RegistrarPagoService | Verificar movimiento y abono, aplicar pago y coordinar publicación |
| CU03 | GestionarDevolucionService | Comprobar autorización, adquirir ejecución y confirmar salida |
| CU04 | EmitirFacturaService | Consultar cuenta saldada, preparar Factura y confirmar emisión adquirida |
| CU05 | EnviarFacturaService | Enviar el documento emitido y registrar constancia |
| CU06 | SuspenderAccesoService | Consultar obligaciones y avisos, evaluar y solicitar baja |
| CU07 | RestablecerAccesoService | Consultar regularización y derecho, evaluar y solicitar alta |
| CU08 | CompletarOperacionPendienteService | Adquirir, revalidar, consultar resultado, confirmar o corregir |
| CU09 | EvaluarIngresoService | Consultar estado, derecho y situación económica para una entrada concreta |

Los servicios reciben actor y correlación desde una entrada autenticada. Obtienen un instante nuevo al comenzar cada fase y vuelven a consultar si cambian los datos o la versión de una raíz. Un evento recibido puede despertar el servicio, pero no sustituye el contrato de consulta que necesita para decidir. Las transacciones financieras y de acceso permanecen independientes aunque se ejecuten dentro del mismo proceso.

### 9.7 Dependencias por capas

**Figura 9.7a. Dependencias del módulo financiero.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  E["Entrada autenticada"] --> A["Application Services financieros"]
  A --> D["CuentaPorCobrar y Factura<br/>Domain Services"]
  A --> P["Interfaces de Repository<br/>y puertos financieros"]
  I["Adaptadores de persistencia<br/>y servicios externos"] -.->|"implementan"| P
```

**Figura 9.7b. Dependencias del módulo de acceso.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
flowchart TB
  E["Entrada autenticada"] --> A["Application Services de Acceso"]
  A --> D["AccesoMiembro<br/>EvaluadorDeIngreso"]
  A --> P["Interfaces de Repository<br/>y puertos de acceso"]
  I["Adaptadores de acceso"] -.->|"implementan"| P
  A --> L["<i>Anti-Corruption Layer</i> financiera"]
  L -->|"consulta por contrato"| F["Fachada pública financiera"]
```

Las dependencias de uso apuntan hacia interfaces y comportamiento del modelo. Los adaptadores implementan los contratos de persistencia, consulta y actuación externa. Las raíces no importan HTTP, el servicio tributario, el proveedor bancario ni el lector de huella. La composición conecta esas implementaciones sin permitir que una entrada administrativa escriba directamente el saldo, la referencia fiscal o el estado del acceso.

### 9.8 Autorización y registro de actividad

La autorización se comprueba en la entrada de cada caso de uso antes de consultar o modificar información protegida. El servidor identifica al actor y resuelve sus permisos sin aceptar el rol que declare una petición. Los procesos automáticos utilizan un permiso de servicio limitado a la coordinación que realizan. Cada actuación registra actor, función, objeto, comando, versiones, motivo y referencia de evidencia protegida.

| Actor | Permisos de operación | Límite |
| --- | --- | --- |
| Administración | LiquidarCuenta, ConciliarPago, GestionarDevolucionAutorizada, EmitirFactura, SolicitarSuspension y SolicitarRestablecimiento | No confirma un acto físico ni aprueba su propia excepción de precio o devolución |
| Recepción | RegistrarComprobante, EnviarFacturaEmitida, AdquirirOperacionHuella, ConfirmarResultadoHuella y AtenderIncidencia | No confirma el abono, cambia saldo o emite una devolución |
| Dirección | AutorizarDescuentoEspecial, AprobarDevolucion, AutorizarAltaInicial y DefinirPolitica | La autorización no reemplaza el abono ni la evidencia física |
| Cliente | InformarPago y consultar sus obligaciones, documentos y derechos | No aplica pagos ni modifica el permiso o los datos de otro cliente |

---

<a id="capitulo-10"></a>

## 10. Casos de uso y coordinación de operaciones

### 10.1 Entradas, permisos y resultados

Los casos de uso describen la coordinación entre información externa y comportamiento del dominio. Una entrada autenticada identifica al actor, su función, el objeto y la correlación de la operación. La aplicación valida estructura, obtiene datos confiables y llama a la raíz o al servicio de dominio correspondiente. Las respuestas distinguen Aplicado, YaAplicado, Pendiente, Rechazado, Conflicto y RequiereRevision, sin crear nuevos estados económicos por cada error.

| Caso | Actor o función | Entrada principal | Resultado observable |
| --- | --- | --- | --- |
| CU01 LiquidarCuenta | Administración | Pagador, período u operación y conceptos confirmados | Cuenta Pendiente con detalle y vencimiento |
| CU02 RegistrarPago | Administración | Cuenta, referencia, comprobante opcional y movimiento | Aplicación verificada, saldo y posible exceso |
| CU03 GestionarDevolucion | Dirección y Administración | Exceso, autorización y ejecución identificada | Devolución confirmada o incierta sin alterar el cobro |
| CU04 EmitirFactura | Administración | Cuenta saldada, datos fiscales y resultado de emisión | Factura PendienteDeEmision o Emitida |
| CU05 EnviarFactura | Recepción y Notificaciones | Factura emitida, destinatario y envío | Enviada solo con constancia |
| CU06 SuspenderAcceso | Administración autorizada | Miembro, obligaciones, avisos y momento | SuspensionPendiente, sin baja ficticia |
| CU07 RestablecerAcceso | Administración autorizada | Regularización, derecho y permiso operativo | RestablecimientoPendiente, sin alta ficticia |
| CU08 CompletarOperacionPendiente | Recepción y coordinador | Operación, intento, evidencia y versión | Confirmación, cancelación o revisión explícita |
| CU09 EvaluarIngreso | Controlador y Recepción | Sujeto, derecho y momento | Evaluación de una entrada concreta |

### 10.2 Liquidar y verificar el cobro de servicios

```text
LiquidarCuentaService.ejecutar(solicitud):
  autorizar Administracion y validar pagador, periodo y moneda
  obtener conceptos confirmados de paquetes, reservas y eventos
  exigir origen, cantidad, precio comprometido y autorizaciones completos
  desglose = CalculoDeCobro.calcular(conceptos, moneda, autorizaciones)
  cuenta = CuentaPorCobrarFactory.crear(identidad, pagador, desglose, vencimiento)
  abrir transaccion financiera
    reservar claves unicas de los cargos
    guardar cuenta y eventos pendientes
  confirmar transaccion y disponer publicacion

RegistrarPagoService.ejecutar(solicitud):
  autorizar conciliacion de Administracion; cargar cuenta objetivo
  obtener transaccion por VerificacionPagoPort
  obtener abono correspondiente por RegistroAbonosPort
  verificacion = ConciliadorDePago.evaluar(comprobanteOpcional, transaccion, abono, cuenta)
  si no se confirma coincidencia: registrar discrepancia y devolver RequiereRevision
  abrir transaccion financiera
    cargar cuenta actual y version
    comprobar asociacion unica de origen y referencia con cuenta
    invocar registrarPagoConfirmado(verificacion, reloj.ahora())
    si nuevo movimiento: guardar raiz, asociacion y eventos juntos
  confirmar transaccion; publicar despues del guardado
  devolver aplicado, exceso y saldo restante
```

El servicio de conciliación no modifica la cuenta si el proveedor todavía no acredita el abono. La raíz reconoce una referencia aplicada antes de comparar el movimiento con el saldo actual. Esa secuencia permite repetir la misma comunicación después de saldar y obtener YaAplicado, sin generar otra aplicación ni devolución. Un movimiento real adicional conserva una referencia diferente y se procesa como exceso cuando la cuenta ya no tiene saldo.

**Figura 10.2a. Obtención de evidencia para conciliar.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  actor A as Administración
  participant S as RegistrarPagoService
  participant P as Verificación de pagos
  participant B as Registro de abonos
  A->>S: Verificar referencia para una cuenta
  S->>P: Consultar transacción
  P-->>S: Importe, moneda, titular y estado
  S->>B: Consultar abono correspondiente
  B-->>S: Abono confirmado o discrepancia
  S->>S: ConciliadorDePago evalúa coincidencia
  S-->>A: PagoVerificado o RequiereRevision
```

**Figura 10.2b. Aplicación y publicación del movimiento.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  participant S as RegistrarPagoService
  participant C as CuentaPorCobrar
  participant P as Persistencia financiera
  participant O as Outbox
  S->>P: Abrir transacción y cargar versión
  P-->>S: Cuenta actual y asociación de referencia
  S->>C: registrarPagoConfirmado con verificación
  C-->>S: Aplicado, YaAplicado o rechazo
  opt Movimiento nuevo aceptado
    S->>P: Guardar raíz y asociación únicas
    S->>O: Guardar eventos en misma transacción
    S->>P: Confirmar commit
    Note over S,O: Publicar únicamente después del commit
  end
```

### 10.3 Devolución de exceso

La devolución comienza con la diferencia conservada entre el importe recibido y el aplicado. Dirección revisa el exceso y autoriza su salida sobre el movimiento original. Administración adquiere una ejecución identificada antes de solicitar la devolución externa y conserva la evidencia del abono de salida. El saldo de la cuenta y las líneas de una factura emitida permanecen asociados a los servicios cobrados, no al exceso devuelto.

```text
GestionarDevolucionService.ejecutar(cuentaId, devolucionId, actor):
  autorizar ejecucion de Administracion; cargar cuenta, exceso y autorizacion de Direccion
  invocar aprobarDevolucion si todavia corresponde; guardar
  si ya Devuelta con resultado identico: devolver SinEfecto
  si EnProceso o Incierta:
    consultar resultado externo
    si salida confirmada: confirmarDevolucion, guardar y devolver
    si resultado no determinable: conservar Incierta y devolver RequiereRevision
    si no salida y cese acreditados: registrarNoEjecucionDevolucion y guardar Aprobada
  si Aprobada: adquirirDevolucion y guardar con version; confirmar commit
  solo el ganador entrega una solicitud con devolucionId estable
  si resultado incierto: registrarResultadoInciertoDevolucion y guardar
  si salida verificada: confirmarDevolucion y guardar resultado
```

El identificador de devolución se conserva entre consultas e intentos recuperados. Una respuesta perdida no demuestra que el dinero permaneció en Nodo. Si no puede determinarse el resultado, la función administrativa conserva la revisión abierta y no adquiere una segunda ejecución. La confirmación posterior idéntica registra una sola salida y permite cerrar la entidad interna.

### 10.4 Preparación, emisión y envío de Factura

```text
EmitirFacturaService.ejecutar(cuentaId, datosFacturacion, actor):
  autorizar emision de Administracion; consultar cuenta y conceptos cerrados
  exigir cuenta Saldada, datos fiscales completos y pagos verificados que cubren su total
  obtener Factura por clave unica cuentaId y tipoDocumento
  si no existe: preparar y reservar esa clave en la misma transaccion
  si otro escritor gana: cargar su Factura; no crear una segunda
  si Factura nueva: guardar PendienteDeEmision con lineas y total inmutables
  si ya Emitida o Enviada: devolver referencia existente
  si hay intento activo o incierto:
    consultar resultado externo
    si emision confirmada: confirmarEmision, guardar y devolver referencia
    si resultado no determinable: conservar intento y devolver RequiereRevision
    si no emision y cese acreditados: registrarNoEmision y guardar
  si no hay ejecucion activa: adquirirEmision y confirmar guardado
  Administracion opera el mecanismo de emision con identidad del intento
  si resultado confirmado: Factura.confirmarEmision y guardar
  si incierto: registrarResultadoInciertoEmision y guardar

EnviarFacturaService.ejecutar(facturaId, destinatario, actor):
  autorizar entrega de Recepcion o proceso de envio; cargar factura Emitida o Enviada
  autorizar destinatario
  solicitar envio con envioId estable
  si enviado con evidencia: Factura.confirmarEnvio y guardar
  si fallo: conservar emision y detalle del intento; no reemitir
```

**Figura 10.4a. Emisión adquirida y confirmada.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  actor A as Administración
  participant S as EmitirFacturaService
  participant F as Factura y persistencia
  participant X as Emisión tributaria
  A->>S: Emitir por cuenta saldada
  S->>F: Obtener o preparar documento único
  S->>F: Adquirir intento y confirmar commit
  S-->>A: Entregar permiso del intento
  A->>X: Emitir con identidad y total
  X-->>A: Resultado y referencia fiscal
  A->>S: Comunicar evidencia correlacionada
  S->>F: ConfirmarEmision y guardar
```

**Figura 10.4b. Envío de una factura ya emitida.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  actor R as Recepción
  participant S as EnviarFacturaService
  participant N as Notificaciones
  participant F as Factura y persistencia
  R->>S: Enviar documento al destinatario autorizado
  S->>F: Consultar factura emitida
  S->>N: Solicitar envío con envioId estable
  alt Constancia de éxito
    N-->>S: ResultadoEnvio confirmado
    S->>F: ConfirmarEnvio y guardar Enviada
  else Fallo o incertidumbre
    N-->>S: Sin constancia de éxito
    S->>F: Registrar intento, conservar emisión
  end
```

La factura no se crea por el mero comprobante que comunica el cliente. La preparación requiere que los pagos verificados hayan completado la obligación, y la emisión exige además un resultado externo correlacionado. El envío ocurre después y puede recuperarse sin emitir un documento nuevo. La misma identidad interna permite consultar la factura durante todos esos pasos, aunque todavía no exista número fiscal.

### 10.5 Solicitud de suspensión o rehabilitación

```text
SuspenderAccesoService.ejecutar(accesoId, actor):
  autorizar Administracion; cargar acceso, version y contexto
  consultar situacion financiera completa y avisos de obligaciones bloqueantes
  evaluacion = evaluarSuspension(contexto, situacion, avisos, reloj.ahora())
  acceso.solicitarSuspension(evaluacion, reloj.ahora())
  guardar operacion Solicitada, estado y evento en transaccion local
  disponer tarea para completar operacion, sin entregar permiso fisico

RestablecerAccesoService.ejecutar(accesoId, actor):
  autorizar Administracion; cargar acceso y contexto actual
  consultar situacion financiera completa y derecho vigente
  evaluacion = evaluarRestablecimiento(contexto, situacion, plan, politica, reloj.ahora())
  acceso.solicitarRestablecimiento(evaluacion, permisoAdministracion, reloj.ahora())
  guardar Alta Solicitada, estado y evento
  disponer tarea para completar operacion, sin afirmar alta realizada
```

La evaluación de suspensión calcula el plazo de cada obligación y el margen de su aviso. No utiliza automáticamente la fecha de renovación para todos los cargos ni suspende a un visitante por una cuenta ajena. La rehabilitación comprueba las obligaciones exigibles del pagador que cubre el derecho y la vigencia actual del plan. El horario de configuración puede diferir del horario de ingreso, por lo que el controlador debe evaluar cada entrada concreta.

### 10.6 Completar una actuación adquirida

```text
CompletarOperacionPendienteService.ejecutar(accesoId, operacionId, actor):
  autorizar operacion de huella de Recepcion; cargar raiz con historial y version
  obtener evidencia opcional por OperacionHuellaPort y validar correlacion
  si terminal identico: devolver SinEfecto
  si evidencia antigua contradictoria:
    raiz.registrarEvidenciaContradictoria; guardar congelacion
    si no hay cese confirmado e inspeccion actual: devolver RequiereRevision
    evaluar contexto actual; reconciliarResultadoFisico y guardar
    devolver resultado de reconciliacion sin entregar una orden antigua
  si EnEjecucion o ResultadoIncierto:
    consultar al mismo ejecutor, sin reenviar
    si resultado no determinable: guardar ResultadoIncierto; devolver RequiereRevision
    si no efecto y cese acreditados: registrarNoEjecucion; guardar Solicitada
    si efecto real: ir a ConfirmarResultado
  si Solicitada:
    si RetiroCorrectivo: comprobar presencia no elegible, cese y permiso de correccion
    si RetiroImpago o Alta: reconsultar datos, construir contexto y reevaluar accion
    si motivo invalidado y no efecto acreditado: cancelar por raiz; guardar y devolver
    si NoEvaluable: mantener revision y devolver RequiereRevision sin permiso fisico
    adquirirEjecucion(operacionId, ejecutorId, reloj.ahora())
    guardar con versionEsperada y confirmar commit
    si conflicto: recargar y devolver Conflicto sin entregar esa orden
    recargar contexto ganador y comprobar que conserva intento y ejecutor
    si RetiroCorrectivo: revalidar incidencia y permiso correctivo
    si ordinaria: reconsultar y reevaluar accion antes del acto
    si cambia motivo: acreditar cese sin efecto; cancelar o mantener revision; guardar y devolver
    entregar una tarea al ejecutor adquirido
    si resultado incierto: guardar ResultadoIncierto y devolver RequiereRevision sin reasignar
  ConfirmarResultado:
    recargar raiz y validar evidencia de tipo, intento, ejecutor y dispositivo
    si baja ordinaria real: confirmarSuspension y guardar
    si alta real: consultar situacion y plan; evaluarRestablecimiento con contexto nuevo
      si favorable: confirmarRestablecimiento y guardar
      si desfavorable o NoEvaluable: registrarAltaNoElegible y guardar
    si baja correctiva real: completarRetiroCorrectivo y guardar
    si alta no elegible cerrada: solicitarRetiroCorrectivo con otra identidad y guardar
    completar esa nueva operacion mediante adquisicion previa
```

**Figura 10.6a. Un único permiso antes de actuar.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  participant A as Coordinador A
  participant B as Coordinador B
  participant P as Acceso y persistencia
  actor R as Recepción
  A->>P: Adquirir OP1 con versión 4
  P-->>A: Commit versión 5 e intento I1
  B->>P: Adquirir OP1 con versión 4
  P-->>B: Conflicto, sin permiso de ejecución
  A->>A: Reconsultar y revalidar motivo
  A->>R: Entregar una tarea OP1 / I1
  R->>R: Realizar actuación autorizada
```

**Figura 10.6b. Recuperación sin repetir el efecto.**

```mermaid
%%{init: {"theme":"dark","themeVariables":{"fontFamily":"Arial","fontSize":"16px","primaryColor":"#21262d","primaryTextColor":"#f0f6fc","primaryBorderColor":"#58a6ff","lineColor":"#8b949e","secondaryColor":"#30363d","tertiaryColor":"#161b22","actorBkg":"#21262d","actorBorder":"#58a6ff","actorTextColor":"#f0f6fc","noteBkgColor":"#30363d","noteTextColor":"#f0f6fc","background":"#0d1117","secondaryTextColor":"#f0f6fc","tertiaryTextColor":"#f0f6fc","textColor":"#f0f6fc","mainBkg":"#21262d","nodeBorder":"#58a6ff","edgeLabelBackground":"#161b22","signalColor":"#c9d1d9","signalTextColor":"#f0f6fc","noteBorderColor":"#8b949e","labelTextColor":"#f0f6fc","labelBoxBkgColor":"#21262d","labelBoxBorderColor":"#8b949e","clusterBkg":"#161b22","clusterBorder":"#8b949e","titleColor":"#f0f6fc","stateLabelColor":"#f0f6fc","darkMode":true},"flowchart":{"htmlLabels":true,"curve":"linear","nodeSpacing":30,"rankSpacing":40,"useMaxWidth":true},"sequence":{"useMaxWidth":true,"actorMargin":24,"messageMargin":34,"noteMargin":14,"wrap":true,"width":200},"state":{"useMaxWidth":true,"padding":12},"fontFamily":"Arial"}}%%
sequenceDiagram
  participant C as Coordinador
  actor R as Recepción
  participant A as Acceso y persistencia
  participant F as Contrato financiero
  R--xC: Resultado de la baja no recibido
  C->>A: Guardar ResultadoIncierto
  C->>R: Consultar al mismo ejecutor, sin reenviar
  R-->>C: Entregar evidencia de baja del intento
  C->>A: Confirmar suspensión y guardar
  C->>F: Consultar obligaciones actuales
  F-->>C: Regularizadas por pago concurrente
  Note over C,A: Evaluar alta en otra operación y transacción
```

Un conflicto después del efecto solo permite recuperar su registro o reconciliar el resultado. Nunca autoriza repetir la actuación que pudo haber ocurrido. La cancelación ordinaria requiere acreditar que no hubo efecto ni operador todavía autorizado para actuar. La evidencia antigua incompatible exige conservar el historial, cesar las autorizaciones anteriores e inspeccionar el estado actual antes de normalizar la situación.

### 10.7 Escenarios de funcionamiento y respuesta

| Situación | Respuesta definida | Estado o información que se conserva |
| --- | --- | --- |
| Plan de equipo consume horas adicionales | Crear conceptos del exceso con descuento comprometido | Pagador titular, consumo y precioVersion |
| Cancelación de sala a tres horas | Cobrar franja completa comunicada por Reservas | Identidad de reserva y motivo del cargo |
| Depósito BOB 200 de evento BOB 1.000 | Liquidar depósito y luego remanente BOB 800 | Dos tramos cuyo total no duplica el anticipo |
| Tarifa cambia después de contratar | Conservar precio ya comprometido | Conceptos y líneas inmutables |
| Comprobante sin abono empresarial | Mantener verificación sin aplicar | Saldo original y discrepancia |
| Pago BOB 200 sobre BOB 480 | Aplicar BOB 200 y conservar BOB 280 | Pendiente, sin factura de cuenta completada |
| Segundo pago BOB 300 | Aplicar BOB 280 y conservar exceso BOB 20 | Saldada y devolución independiente |
| Misma referencia repetida | Responder YaAplicado antes de comparar saldo | Una aplicación y datos originales |
| Referencia utilizada en otra cuenta | Rechazar asociación incompatible | Conflicto auditable sin segunda imputación |
| Factura pedida antes de completar pago | No preparar documento definitivo | Cuenta pendiente y motivo de rechazo |
| Emisión externa incierta | Consultar intento antes de otra emisión | PendienteDeEmision e intento conservado |
| Envío de factura falla | Reintentar envío sin reemitir | Estado previo de Factura y registro del envío |
| Antes del quinto día desde vencimiento | No solicitar suspensión por esa obligación | Habilitado y seguimiento de cobro |
| Quinto día exacto y aviso suficiente | Solicitar suspensión si persiste motivo | SuspensionPendiente, todavía sin baja confirmada |
| Aviso tardío o fallido | Esperar envío exitoso y veinticuatro horas | Sin permiso físico de corte |
| Pago antes de baja no ejecutada | Cancelar con evidencia de no efecto y cese | Operación Cancelada y Habilitado |
| Pago durante baja real | Confirmar hecho y evaluar alta posterior | Suspendido con evidencia y coordinación de rehabilitación |
| Alta real pierde elegibilidad | Cerrar alta sin habilitar y crear baja correctiva | Presente e incidencia hasta ausencia confirmada |
| Dos coordinadores intentan actuar | Solo el ganador adquirido entrega tarea | Un ejecutor; perdedor recarga |
| Plan normal fuera de horario | Denegar entrada aunque exista registro | Evaluación Ingresar desfavorable |
| Plan continuo vigente y regularizado | Evaluar entrada sin restricción de franja normal | Permiso condicionado a estado físico confiable |
| Visitante de evento | Consultar derecho temporal del evento | Identidad y vigencia del derecho, sin crear renovación |

### 10.8 Evaluación de una entrada

El caso de uso distingue el derecho de un miembro del derecho temporal de una contratación. El primero utiliza AccesoMiembro y las obligaciones exigibles de su pagador dentro del alcance definido. El segundo consulta un derecho confirmado por Reservas y disponibilidad o Agenda de eventos y no crea una membresía. La apertura temporal es supervisada por Recepción, mientras la apertura ordinaria exige que el controlador respete la evaluación vigente.

```text
EvaluarIngresoService.ejecutar(sujetoId, derechoId):
  autenticar solicitante y consultar derecho por contrato autorizado
  momento = reloj.ahora() del servidor; no aceptar la hora libre del cliente
  si derecho de miembro:
    cargar AccesoMiembro y contexto actual
    consultar situacion financiera completa y plan vigente
    devolver evaluarIngreso(contexto, situacion, plan, momento)
  si derecho temporal:
    consultar confirmacion del subdominio responsable
    devolver evaluarDerechoTemporal(derechoConfirmado, momento)
  si derecho ausente, incompleto o ajeno: devolver NoEvaluable
```
