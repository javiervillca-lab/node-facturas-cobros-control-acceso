# Especificación técnica del modelo

Esta carpeta contiene la estructura de interfaces, persistencia y coordinación externa de la solución. El alcance comprende los módulos de Facturación y Cobros y Control de Acceso y sus tres raíces de consistencia. Las interfaces describen cómo separar consultas confiables, decisiones de dominio y actuaciones externas. El contenido es documental y no acredita una conexión bancaria, fiscal o física instalada.

- [Interfaces, persistencia y coordinación](ESPECIFICACION_FUTURA_PROTOTIPO_Y_PRUEBAS.md).
- [Modelo completo](../00_ENTREGABLE_COMPLETO.md).
- [Contratos de integración](../01_Documento_Principal/06_Patrones_y_Contratos_de_Integracion.md).

CuentaPorCobrar protege cargos, aplicaciones y excesos. Factura protege el documento cobrado y su resultado de emisión y envío. AccesoMiembro protege el permiso y la operación de huella. Los parámetros del proveedor y del dispositivo se configuran mediante adaptadores que conservan las identidades, versiones y evidencias descritas.
