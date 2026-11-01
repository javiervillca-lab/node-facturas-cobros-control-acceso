# 06. Reglas e invariantes

Las reglas de este documento describen las invariantes de los dos *Bounded Contexts*. La frontera es entre Facturación y Cobros y Control de Acceso, no entre Facturación y Cobros como módulos internos.

## Facturación y Cobros — *Generic*

### CuentaPorCobrar y pagos

- La cuenta conserva pagador, origen, conceptos, moneda, total, vencimiento, saldo, estado y movimientos con trazabilidad.
- El saldo no puede ser negativo; una cuenta queda `Saldada` cuando el saldo pendiente es cero.
- Solo se aplica un movimiento verificado, con identidad y moneda comparadas con la obligación.
- Un pago parcial conserva el saldo pendiente y no habilita por sí solo la emisión de una factura final.
- El importe recibido que supera el saldo se registra como exceso ligado a la referencia original; no se aplica por encima del total debido.
- La suma devuelta y pendiente de devolución no puede superar el exceso verificado; la devolución autorizada conserva su evidencia.
- La cuenta saldada no se reabre por un pago posterior; el movimiento adicional se procesa como exceso.

### Factura

- La factura refleja los conceptos efectivamente cobrados y su total coincide con las líneas documentales.
- Una factura ordinaria solo se prepara cuando la cuenta correspondiente está saldada y se dispone de los datos requeridos.
- El estado de la factura es documental y no sustituye al estado o saldo de la cuenta.
- Una factura se considera emitida solo con resultado externo confirmado, referencia fiscal y total verificados.
- Una emisión de resultado incierto no se repite a ciegas; primero se resuelve su resultado.

### Invariantes entre agregados del contexto

- `CuentaPorCobrar` y `Factura` son raíces distintas en el mismo contexto financiero.
- La identidad de la cuenta y los conceptos se conservan al preparar la factura, sin facturar el exceso.
- Los efectos externos se confirman y registran; la solicitud no equivale a un resultado completado.

## Control de Acceso — *Generic*

- El ingreso se evalúa para un sujeto, acción e instante con base en un derecho válido, su alcance, vigencia y restricciones.
- El estado financiero se consume mediante el contrato público y se traduce a `SituacionPago`; el contexto no consulta repositorios ni entidades financieras.
- La suspensión o rehabilitación distingue entre permiso lógico y resultado físico en el registro de huella.
- Una solicitud de actuación no se considera confirmación; los intentos, responsable y evidencia deben conservarse.
- Una operación incierta requiere revisión y recuperación explícitas; no se afirma que la huella haya cambiado sin evidencia.
- Las decisiones denegadas, suspendidas o en revisión conservan motivo y correlación suficientes para auditoría.

## Reglas de integración

- La comunicación entre contextos utiliza identificadores y contratos, no agregados compartidos.
- Ninguna política de acceso puede cambiar el saldo financiero; ningún cambio de saldo confirma una actuación física.
- Cada contexto protege sus invariantes en su modelo; validaciones de persistencia refuerzan, pero no sustituyen, esas reglas.
