# Contexto: Control de acceso

## Propósito

Este contexto valora si una persona o entidad puede entrar a un espacio, participar en un evento o utilizar un servicio bajo condiciones determinadas. Debe conservar la separación entre autorización, evidencia y resultados operativos.

## Responsabilidades

- validar membresías, reservas, pases y vigencias,
- autorizar o denegar el ingreso según el estado del cliente y la política del acceso,
- registrar entradas, salidas y eventos relevantes de auditoría,
- coordinar con el negocio de facturación y cobros cuando el acceso dependa de cuota, pago o condiciones comerciales,
- gestionar operaciones de huella y reactivación de permisos cuando corresponda.

## Conceptos clave

- usuario o miembro,
- permiso,
- pase,
- reserva,
- autorización,
- registro de acceso,
- operación de huella,
- suspensión o restauración del acceso.

## Invariantes principales

- un acceso solo se autoriza si existe una base válida de permisos o reserva,
- la vigencia del pase debe validarse al momento de la autorización,
- la operación de huella debe distinguir entre solicitud y confirmación,
- las denegaciones y resultados inciertos deben conservarse como evidencia para auditoría.

## Eventos de dominio sugeridos

- `ReservaConfirmada`
- `AccesoAutorizado`
- `AccesoDenegado`
- `MiembroHabilitado`
- `MiembroSuspendido`
- `RegistroHuellaSolicitado`
- `RegistroHuellaConfirmado`

## Capas sugeridas

- `domain/`: entidades, agregados y políticas de acceso.
- `application/`: validación de permisos, autorización y coordinación de eventos.
- `infrastructure/`: integraciones con dispositivos, persistencia y adaptadores de acceso.
- `interfaces/`: APIs, listeners y endpoints de gestión de eventos de acceso.

## Estado actual

El contexto está definido como una frontera separada con reglas de autorización y evidencia bien diferenciadas del dominio financiero. La implementación específica de permisos y operación de huella es una etapa posterior.
