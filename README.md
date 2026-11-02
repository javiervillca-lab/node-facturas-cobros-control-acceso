# Nodo — Facturación y Cobros y Control de Acceso

Prototipo académico de un coworking desarrollado con Symfony, Doctrine y PostgreSQL. Modela obligaciones, pagos, facturas y el ciclo de suspensión y restablecimiento de acceso mediante integraciones simuladas.

**Empieza por el [documento principal de diseño](docs/00_ENTREGABLE_COMPLETO.md).** Allí están el contexto del negocio, los diagramas, las tres raíces, las invariantes I01–I16 y los contratos propuestos. Para ejecutar el proyecto utiliza la [guía de instalación](docs/INSTALACION_Y_EJECUCION_DOCKER.md).

## Qué contiene el modelo

| Bounded Context | Aggregate roots | Responsabilidad |
|---|---|---|
| Facturación y Cobros | CuentaPorCobrar y Factura | Obligación, aplicación de pagos, excesos, devolución y documento fiscal |
| Control de Acceso | AccesoMiembro | Derecho operativo, suspensión, restablecimiento y resultado de huella |

Son **dos contextos y tres raíces**. `src/cobros` y `src/facturacion` organizan módulos del mismo contexto financiero. Acceso consulta el contrato financiero mediante un adaptador y lo traduce a SituacionPago. Reservas y disponibilidad es el core del negocio; las dos áreas desarrolladas se clasifican como genéricas.

## Arranque local

Necesitas Git, Docker y Docker Compose v2. No necesitas instalar PHP, Composer, Node ni Make en el equipo si utilizas Docker. La primera construcción descarga imágenes y dependencias.

```bash
git clone https://github.com/javiervillca-lab/node-facturas-cobros-control-acceso.git
cd node-facturas-cobros-control-acceso
```

Define el argumento requerido por el Dockerfile. En **PowerShell**:

```powershell
$env:userid = "1000"
```

En **Linux o WSL**, con un usuario normal:

```bash
export userid=$(id -u)
```

Después, desde la raíz del proyecto:

```bash
docker compose up -d --build
docker compose exec db pg_isready -U faccob -d faccob
docker compose exec node-fac-cob composer install --no-interaction
docker compose exec node-fac-cob php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec node-fac-cob php bin/console app:demo:preparar-acceso
```

Espera a que `pg_isready` indique que PostgreSQL acepta conexiones antes de migrar. El comando de preparación imprime la cuenta y la ruta del acceso creado; no presupongas que su ID es 1. Abre **[http://localhost:8008](http://localhost:8008)** y consulta Clientes, Cuentas, Facturas y Accesos.

La base `faccob` y sus credenciales de `.env`/Compose son valores del entorno local. Prepara el escenario solo en una base local desechable, sin datos que quieras conservar. El comando reconoce el estado inicial, pero no reinicia una demo que ya avanzó. Consulta [instalación, puertos y solución de problemas](docs/INSTALACION_Y_EJECUCION_DOCKER.md).

## Pruebas

Pruebas de dominio, sin crear una base de pruebas:

```bash
docker compose exec node-fac-cob php bin/phpunit tests/cobros/domain
docker compose exec node-fac-cob php bin/phpunit tests/control_acceso/domain
```

Para ejecutar toda la suite prepara antes la base de pruebas, que Doctrine configura con el sufijo `_test`:

```bash
docker compose exec node-fac-cob php bin/console doctrine:database:create --env=test --if-not-exists
docker compose exec node-fac-cob php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec node-fac-cob php bin/phpunit
```

La suite incluye pruebas de dinero decimal, pagos repetidos, exceso, factura y ciclo de acceso. Ejecuta las pruebas de integración sobre la conexión de test, no contra datos de uso normal.

## Funcionamiento del prototipo

El prototipo permite comprobar pagos parciales, referencias repetidas, excesos, emisión y envío simulados, y el ciclo de aviso, espera, suspensión, pago y restablecimiento. La prueba integrada adelanta la fecha del aviso para representar las 24 horas; la interfaz no omite ese plazo.

Pagos, devoluciones, emisión fiscal, notificaciones, derechos y huella utilizan simuladores de desarrollo. No equivalen a movimientos bancarios, comprobantes fiscales ni actuaciones reales. El [documento principal](docs/00_ENTREGABLE_COMPLETO.md) reúne las reglas y los contratos del modelo.

## Documentación y estructura

- [Documento principal completo](docs/00_ENTREGABLE_COMPLETO.md).
- [Índice de documentación y consultas temáticas](docs/README.md).
- [Instalación y ejecución](docs/INSTALACION_Y_EJECUCION_DOCKER.md).
- [Mapa de módulos y capas](src/README.md).
- [Decisión de fronteras](decisiones/0001-por-que-esta-frontera.md) y [clasificación estratégica](decisiones/0002-clasificacion-del-area.md).

```text
docs/                   Modelo completo, consultas temáticas y guías de ejecución
decisiones/             Decisiones de frontera y clasificación
src/cobros/             Módulo financiero de cuentas, pagos y devoluciones
src/facturacion/        Módulo financiero de factura, emisión y envío
src/control_acceso/     Contexto de acceso, contratos y simuladores
src/Entity/             Cliente y su persistencia
src/Migrations/         Evolución del esquema Doctrine
templates/              Interfaz Twig
tests/                  Dominio, integración y preparación de demo
docker/                 PHP, PostgreSQL y Nginx
```

## Apagar y volver a iniciar

```bash
docker compose stop
docker compose start
```

Estos comandos conservan la base y el escenario. Reiniciar contenedores no reinicia una demo avanzada. Evita `down -v`, que elimina los volúmenes de datos del proyecto.
