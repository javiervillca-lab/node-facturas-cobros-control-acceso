# Especificación técnica de interfaces y persistencia

## 1. Arquitectura de aplicación

La solución se organiza como un monolito modular con Facturación y Cobros y Control de Acceso. Cada módulo separa dominio, aplicación, puertos y adaptadores. Las raíces se cargan y guardan mediante interfaces que pertenecen al modelo y no a un proveedor de base de datos. El dominio no conoce HTTP, credenciales externas ni el protocolo del lector de huella.

```text
nodo/
  facturacion_cobros/
    dominio/       CuentaPorCobrar, Factura, valores, entidades y eventos
    aplicacion/    liquidacion, conciliacion, devolucion, emision y envio
    puertos/       servicios, pagos, abonos, devolucion, emision y avisos
    adaptadores/   almacenamiento, conectores y captura administrativa
  control_acceso/
    dominio/       AccesoMiembro, OperacionRegistroHuella y evaluador
    aplicacion/    suspension, rehabilitacion, coordinacion e ingreso
    puertos/       situacion financiera, derechos, huella y avisos
    adaptadores/   ACL financiera, almacenamiento y controlador
  entrada/         API y funciones administrativas
  composicion/     conexion de interfaces, permisos y reloj
```

La estructura representa responsabilidades y no exige una clase por cada carpeta. CuentaPorCobrar, Factura y AccesoMiembro son las tres raíces persistibles. DevolucionExceso y OperacionRegistroHuella permanecen dentro de la frontera que las contiene. La captura administrativa y las futuras entradas de API invocan los mismos casos de uso para evitar una segunda implementación de las reglas.

## 2. Tipos y campos persistidos

| Objeto | Campos mínimos | Restricción |
| --- | --- | --- |
| CuentaPorCobrar | cuentaId, pagadorId, origen, conceptos, moneda, total, saldo, estado y versión | Saldo entre cero y total; conceptos cerrados |
| AplicacionPago | origen, referencia, recibido, aplicado, exceso, verificacionId y tiempos | Misma referencia conserva datos originales |
| DevolucionExceso | devolucionId, movimiento, monto, aprobación, estado, ejecutor y resultado | No superar exceso pendiente ni adquirir dos ejecuciones |
| Factura | facturaId, cuentaId, tipoDocumento, líneas, datos fiscales, total, estado y versión | Una factura ordinaria por cuenta y tipo |
| IntentoEmision | intentoId, ejecutor, fecha, identidad externa y resultado | No repetir emisión mientras el resultado es incierto |
| AccesoMiembro | accesoId, miembroId, pagadorId, planId, estado, política, observación e incidencia | Mutación por raíz con versión |
| OperacionRegistroHuella | operación, tipo, parent, estado, intentos y evidencia | Una no terminal por acceso y un ejecutor por intento |
| MensajePendiente | messageId, raíz, versión, tipo, contenido mínimo y estado de entrega | Guardado junto con el cambio local |

Los importes usan aritmética decimal exacta y moneda explícita. Las fechas usan zona y conservan el instante del acto separado del de recepción. Las referencias de transacción y los identificadores de origen no se normalizan de una manera que pierda significado del proveedor. Los documentos sensibles se almacenan mediante una referencia protegida y el registro de integración evita copiarlos completos.

## 3. Restricciones de almacenamiento

La unidad de persistencia de cada raíz conserva todos sus componentes internos y eventos pendientes. La escritura compara la versión esperada y devuelve conflicto si otro escritor ya modificó el objeto. La aplicación recarga antes de decidir si corresponde insistir, reconocer una entrega repetida o registrar una discrepancia. El efecto externo no se repite para resolver un conflicto producido después de que ya pudo ocurrir.

- Clave única de cargo: origen de servicio, tipo y período o tramo.
- Clave única de movimiento: proveedor u origen y referencia, asociada a una cuenta.
- Clave única de factura ordinaria: cuentaId y tipoDocumento.
- Clave única de recepción de integración: productor y messageId.
- Una operación de huella no terminal por acceso y un intento activo adquirido por operación.
- Guardado atómico de asociación de movimiento, aplicaciones, exceso y eventos financieros.

## 4. Contrato HTTP y autenticación

La API usa HTTPS, documentos JSON y rutas versionadas. El servidor autentica al actor y calcula sus permisos, sin confiar en el rol que declare una petición. Los comandos no idempotentes incorporan una identidad de solicitud y los cambios administrativos incluyen la versión conocida del objeto. Los detalles de protocolo se traducen a comandos de aplicación antes de ingresar al dominio.

| Entrada | Contrato básico | Respuesta |
| --- | --- | --- |
| Crear cuenta | `POST /api/v1/cuentas` con pagador, conceptos confirmados y vencimiento | Cuenta creada o conflicto de origen |
| Informar pago | `POST /api/v1/pagos/informados` con referencia y comprobante opcional | Registro de comunicación en verificación |
| Verificar movimiento | `POST /api/v1/pagos/{pagoId}/verificaciones` | Verificación o discrepancia, sin confirmación declarada por cliente |
| Aplicar pago | `POST /api/v1/cuentas/{cuentaId}/aplicaciones` con verificacionId | Recibido, aplicado, exceso, saldo y versión |
| Devolver exceso | Rutas de aprobación y ejecución por devolucionId | Devuelta, Pendiente o Incierta |
| Preparar factura | `POST /api/v1/facturas` con cuenta saldada y datos del titular | Factura existente o PendienteDeEmision |
| Emitir factura | Ruta de adquisición y ruta de resultado de emisión | Emitida solo con referencia y total verificados |
| Consultar situación | `GET /api/v1/pagadores/{pagadorId}/situacion` | Obligaciones, alcance, completitud, tiempos y versiones |
| Solicitar acceso | Rutas de suspensión y restablecimiento por accesoId | Operación Solicitada y estado pendiente |
| Completar actuación | Rutas de adquisición y resultado por operacionId | Estado actualizado o RequiereRevision |

```json
{
  "cuentaId": "CPC-001",
  "referencia": "TX-001",
  "verificacionId": "VER-001",
  "versionEsperada": 2,
  "correlationId": "COB-001"
}
```

El importe verificado se obtiene del registro de verificación protegido, no de un valor libre de la petición de aplicación. Una solicitud repetida con la misma identidad y los mismos datos devuelve el resultado conocido. La misma identidad con contenido diferente se rechaza como conflicto. La respuesta distingue un comando aceptado para coordinación de una actuación externa ya confirmada.

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

## 5. Proveedor de pagos y conciliación

VerificacionPagoPort consulta la transacción y RegistroAbonosPort identifica su abono empresarial. ConciliadorDePago compara origen, referencia, titular, monto y moneda, y diferencia una autorización de tarjeta de un abono confirmado. El comprobante del cliente ayuda a identificar el movimiento, pero no reemplaza los datos consultados por la empresa. La discrepancia conserva su motivo sin aplicar un pago provisional como si estuviera confirmado.

Un *webhook* firmado puede despertar la consulta de una transacción. El receptor verifica firma, identidad del proveedor, tiempo y duplicación del mensaje antes de aceptar la comunicación. La aplicación consulta el resultado confiable y el abono para producir PagoVerificado. El proveedor no accede a la raíz ni define el saldo o el estado de la factura.

## 6. Emisión, devolución y actuación adquiridas

Una actuación externa se adquiere mediante escritura con versión y confirmación del guardado. Solo después se entrega la tarea al responsable identificado. Una caída posterior conserva la asignación y obliga a consultar el resultado o acreditar cese y no ejecución. El tiempo transcurrido no equivale a ausencia de efecto y no permite conceder automáticamente otra ejecución.

La devolución conserva devolucionId y verifica la salida efectiva antes de pasar a Devuelta. La emisión conserva facturaId, intento y referencia fiscal disponible para consultar el resultado. La operación de huella conserva operacionId, intento, ejecutor y dispositivo, y exige evidencia del efecto físico. Los tres adaptadores responden con resultado confirmado, no ejecutado o incierto, sin ocultar esa distinción detrás de un booleano.

## 7. Mensajería y actualización de vistas

El cambio de raíz y su mensaje pendiente se guardan juntos mediante *outbox*. Los consumidores conservan las identidades recibidas mediante *inbox* y aplican idempotencia de negocio adicional. Una falla de entrega no revierte el saldo que ya fue confirmado y la publicación se recupera después. El evento de cuenta saldada provoca consultas para preparar Factura o reevaluar acceso, sin transportar entidades mutables.

El canal *WebSocket* o *Server-Sent Events* comunica cambios de estado a una vista autorizada. El mensaje identifica objeto, versión y tipo de cambio, y permite refrescar su representación por la API. Después de desconexión se consulta el estado actual y no se reconstruye la verdad del dominio con mensajes que pudieron perderse. Este canal no contiene permisos para ejecutar devoluciones, emitir documentos ni actuar sobre el registro de huella.

## 8. Configuración de operación

| Parámetro | Valor de la solución | Uso |
| --- | --- | --- |
| Zona | America/La_Paz | Cálculo y presentación de instantes |
| Tolerancia | Cinco días calendario desde vencimiento | Evaluar deuda bloqueante |
| Aviso | Veinticuatro horas después de envío exitoso | Calcular fecha efectiva de corte |
| Horario normal | Lunes a viernes, 08:00 a 18:00 con fin excluido | Derechos que no conceden acceso continuo |
| Antigüedad financiera | Hasta treinta segundos y consulta en fase actual | Rechazar información no confiable para decidir |
| Roles | Recepción administrativa, emisión y operación; Dirección autorizadora | Permisos y registro de cada actuación |
| Datos de ejemplo | Cuota BOB 400 y extras BOB 80 | Explicar cuenta de BOB 480 sin fijar tarifas comerciales |

La configuración versiona las políticas y no reinterpreta eventos anteriores. Las cuentas y facturas cerradas conservan precio y condiciones originales. El adaptador del controlador debe poder respetar el horario y acreditar el estado del registro sin introducir biometría en el sistema. La elección concreta de proveedor, mecanismo fiscal y dispositivo completa la configuración técnica, manteniendo los mismos contratos de dominio.
