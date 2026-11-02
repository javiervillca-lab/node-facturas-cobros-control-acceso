# EventStorming de cobros y acceso

Consulta temática del [documento principal](00_ENTREGABLE_COMPLETO.md), con los diagramas, conceptos y reglas correspondientes.

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
