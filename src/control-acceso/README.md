# Contexto: Control de acceso

## Propósito

Gestionar la autorización de ingreso, validación de permisos y registro de accesos en coworking y eventos.

## Responsabilidades

- Validar membresías, reservas y pases activos.
- Autorizar o denegar ingreso.
- Registrar entradas y salidas.
- Asociar el acceso con usuarios, eventos o espacios.

## Capas sugeridas

- `domain/`: entidades, value objects, agregados y políticas de acceso.
- `application/`: casos de uso de validación, autorización y registro.
- `infrastructure/`: integraciones con dispositivos, APIs y persistencia.
- `interfaces/`: endpoints, listeners y eventos externos.

## TODO

- Definir agregados para permisos, usuarios y accesos.
- Identificar reglas de negocio para autorización.
- Definir eventos de control de acceso.
- Establecer integración con facturación y cobros.
