# Bounded Context: Control de Acceso — *Generic*

## Propósito

Este contexto evalúa el derecho de una persona a ingresar a un espacio, participar en un evento o utilizar un servicio bajo condiciones determinadas. Protege la autorización y la coordinación de operaciones físicas, sin confundir una decisión lógica con el resultado sobre el registro de huella.

## Responsabilidades

- evaluar derechos, membresías, reservas, pases, vigencias y restricciones,
- decidir el ingreso según las reglas de acceso y la situación relevante,
- conservar decisiones, motivos, resultados y evidencia para auditoría,
- coordinar suspensiones, rehabilitaciones y operaciones sobre la huella,
- consultar la situación económica a través del puerto financiero y traducirla a su propio lenguaje.

## Lenguaje del contexto

`AccesoMiembro`, `SituacionPago`, `EvaluacionAcceso` y `OperacionRegistroHuella` pertenecen a este contexto. Los datos financieros llegan mediante el contrato público de Facturación y Cobros; este contexto no consulta sus repositorios ni modifica sus agregados.

Una operación solicitada o adquirida no acredita que el efecto físico haya ocurrido. Los resultados confirmados e inciertos deben conservarse y recuperarse explícitamente.

## Estructura

- `domain/`: agregados, valores y políticas de acceso.
- `application/`: evaluación y coordinación de operaciones.
- `infrastructure/`: persistencia, ACL y adaptadores de dispositivos.
- `interfaces/`: entradas administrativas y APIs.

Las reglas, estados y relaciones del dominio se describen en la documentación del proyecto. El diseño objetivo no implica que todos los flujos o adaptadores estén implementados.
