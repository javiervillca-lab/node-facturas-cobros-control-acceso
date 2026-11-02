# 04. Historias de dominio

Estas historias describen los dos *Bounded Contexts* de Nodo. Los casos de uso son objetivos del modelo y no una afirmación de que todos estén implementados actualmente.

## Facturación y Cobros — *Generic*

1. Como Recepción en función administrativa, quiero liquidar conceptos autorizados de un mismo pagador, moneda y período o tramo para crear una obligación trazable.
2. Como cliente, quiero informar un pago para que la empresa verifique el movimiento antes de aplicarlo a mi cuenta.
3. Como Recepción, quiero aplicar un pago verificado al saldo pendiente y conservar el exceso, si lo hubiera, para no duplicar ni perder dinero.
4. Como Dirección, quiero autorizar una devolución de exceso y que Recepción confirme su resultado para conservar la trazabilidad del movimiento.
5. Como responsable de emisión, quiero preparar la factura por los conceptos efectivamente cobrados y confirmar la emisión externa para mantener el estado documental separado del saldo de la cuenta.

## Control de Acceso — *Generic*

1. Como Recepción, quiero evaluar un ingreso según el sujeto, su derecho vigente, el horario y la situación relevante para autorizar o denegar con un motivo identificable.
2. Como responsable de acceso, quiero consultar la situación financiera mediante el contrato público para aplicar la política de acceso sin depender del modelo interno de cobros.
3. Como Recepción, quiero adquirir y ejecutar una operación de baja o registro de huella, y confirmar el resultado real para no confundir una solicitud con un efecto físico completado.
4. Como Dirección, quiero que una suspensión o rehabilitación conserve la política, el aviso requerido y la evidencia de la actuación para poder auditar la decisión.

## Colaboración entre contextos

Cuando una situación financiera influye en el acceso, Facturación y Cobros publica la información necesaria y Control de Acceso la traduce a `SituacionPago`. El contexto de acceso decide según sus propias reglas y derechos; no modifica cuentas, pagos ni facturas. Una decisión de acceso tampoco altera ni confirma el estado financiero.

Los detalles de estados, precondiciones y recuperación se amplían en el [modelo de dominio](./00_ENTREGABLE_COMPLETO.md).
