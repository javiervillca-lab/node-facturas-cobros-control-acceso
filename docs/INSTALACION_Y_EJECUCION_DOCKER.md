# Instalación y ejecución local

[Volver al README](../README.md) · [Documento principal](00_ENTREGABLE_COMPLETO.md)

## Requisitos y versiones declaradas

Git, Docker y Docker Compose v2, con acceso a Internet. En Windows, inicia Docker Desktop con contenedores Linux. Make es opcional: los pasos siguientes usan Compose directamente.

| Componente | Fuente del requisito |
|---|---|
| PHP | Dockerfile: `php:8.4-fpm-alpine`; la suite del lock requiere PHP >=8.4.1 |
| Symfony | 7.4; el lock fija FrameworkBundle v7.4.22 |
| Doctrine ORM | El lock fija 3.7.5 |
| PHPUnit | El lock fija 13.4.5 |
| PostgreSQL | Dockerfile: `postgres:16.1-alpine` |
| Nginx | Dockerfile: `nginx:alpine`, etiqueta mutable |

No confundir los requisitos de `composer.json` para la aplicación (PHP >=8.2) con los de desarrollo de `composer.lock`. Instala desde el lock, sin `composer update`. Las etiquetas de imagen pueden variar; consulta `docker compose exec node-fac-cob php -v` para conocer la versión efectiva.

## Obtener, configurar y arrancar

```bash
git clone https://github.com/javiervillca-lab/node-facturas-cobros-control-acceso.git
cd node-facturas-cobros-control-acceso
```

Define `userid`, requerido al construir PHP. PowerShell: `$env:userid = "1000"`. Linux/WSL con usuario normal: `export userid=$(id -u)`. No uses UID 0 para crear `appuser`.

`.env` contiene la conexión local de ejemplo `postgresql://faccob:admin@db:5432/faccob?serverVersion=16&charset=utf8`. El host es `db` y su puerto interno es `5432`; el puerto `5439` solo se utiliza para acceder desde el equipo anfitrión. Para cambiar la conexión, utiliza `.env.local`, que Git ignora, y mantén coherencia con el servicio PostgreSQL de Compose. Nunca uses credenciales de producción.

```bash
docker compose up -d --build
docker compose ps
docker compose exec db pg_isready -U faccob -d faccob
```

Espera a que la base acepte conexiones. Después:

```bash
docker compose exec node-fac-cob composer install --no-interaction
docker compose exec node-fac-cob php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec node-fac-cob php bin/console app:demo:preparar-acceso
```

El comando muestra la ruta real del escenario. Usa una base de desarrollo desechable; el comando bloquea `prod`, pero no exige actualmente que la base se llame `faccob_demo`.

| Servicio | Función | Puerto del equipo |
|---|---|---|
| faccob-web | Sitio Nginx | [localhost:8008](http://localhost:8008) |
| node-fac-cob | PHP-FPM | Sin puerto público |
| db | PostgreSQL | 5439, interno 5432 |
| mailer | Mailcatcher | [localhost:1081](http://localhost:1081), SMTP 1026 |

La emisión, entrega y avisos usan simuladores; Mailcatcher no demuestra que estos adaptadores hayan enviado un correo.

## Pruebas y datos de ejemplo

Los comandos de [pruebas del README](../README.md#pruebas) preparan `faccob_test` y ejecutan la suite. Si sobrescribes DATABASE_URL, comprueba la conexión efectiva del entorno test antes de migrar. `phpunit.dist.xml` fuerza APP_ENV=test y Doctrine añade `_test` al nombre base.

`app:demo:preparar-acceso` conserva el escenario mientras siga inicial; rechaza reiniciarlo si avanzó. `app:seed` crea otros clientes y cuentas de ejemplo, pero no es idempotente; no es necesario para la demo de acceso y no debe repetirse sobre los mismos datos.

No se distribuye `docker-compose.demo.yml` ni `docker-compose.prod.yml` en esta copia. `docker/nginx/demo.conf` por sí solo no configura otro stack. Para una demo completamente independiente crea otra copia/entorno con sus propios volúmenes, nombres de contenedor y puertos; los nombres fijos actuales impiden levantar dos copias simultáneas sin ajustarlos.

## Problemas frecuentes

- **Docker no responde:** inicia Docker Desktop/Engine y comprueba `docker compose version`.
- **Argumento userid vacío:** define la variable en la terminal donde construyes la imagen.
- **Puerto ocupado:** revisa 8008, 5439, 1081 y 1026; cambia el puerto del host en Compose si corresponde.
- **Fallo de conexión:** confirma que la URL dentro del contenedor usa `db:5432` y que PostgreSQL está listo.
- **Tablas inexistentes:** ejecuta las migraciones en el entorno correcto; para PHPUnit prepara también la base `_test`.
- **No aparece vendor/autoload_runtime.php:** ejecuta `composer install` dentro de PHP con sus plugins habilitados.
- **El escenario ya avanzó:** el comando no restablece datos. No borres una base compartida para reintentar.

## Logs, parada y Makefile

```bash
docker compose logs --tail=100 node-fac-cob faccob-web db
docker compose stop
docker compose start
```

El Makefile utiliza la variante antigua `docker-compose` y comandos Unix; no es necesario para esta instalación. Sus objetivos de producción apuntan a un Compose ausente y `ssh-schema-update` apunta a un script ausente. Para actualizar el esquema utiliza las migraciones anteriores. Evita `down -v` salvo que quieras eliminar expresamente los datos locales.
