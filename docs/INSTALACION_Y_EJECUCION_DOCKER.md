# Instalación y ejecución con Docker

Esta guía describe cómo construir y ejecutar Nodo en el entorno local de desarrollo. Los comandos se ejecutan desde la raíz del repositorio.

## Requisitos

- Docker Engine con soporte para Docker Compose.
- GNU Make, porque los comandos principales se ejecutan mediante el `Makefile`.
- Git para obtener el repositorio.
- Acceso a Internet durante la primera construcción de las imágenes y la instalación de dependencias.

Las versiones observadas en el entorno de desarrollo el **1 de noviembre de 2026** fueron:

| Componente | Versión o imagen | Cómo se determina |
|---|---|---|
| Docker Engine | 20.10.14 | Versión instalada en el entorno consultado; puede ser distinta en otros equipos. |
| Docker Compose standalone | 1.29.2 | Es la variante que invocan los objetivos `build`, `up` y `stop` del `Makefile`. |
| Docker Compose plugin | v2.4.1 | También estaba instalado; algunos comandos auxiliares que no tienen objetivo en el `Makefile` pueden usar `docker compose`. |
| PHP | 8.4.27 | PHP instalado en el contenedor en ejecución. La imagen se declara como `php:8.4-fpm-alpine`. |
| Composer | 2.10.3 | Versión observada en el contenedor. El Dockerfile descarga Composer y ejecuta `self-update`, por lo que no está fijada a una versión concreta. |
| PostgreSQL | 16.1 | La imagen se declara como `postgres:16.1-alpine`. La URL de Doctrine indica `serverVersion=16`. |
| Nginx | 1.31.8 | Versión observada en el contenedor. La configuración usa `nginx:alpine`, una etiqueta mutable. |
| Symfony Framework | 7.4.22 | Versión instalada según `composer.lock`; la rama configurada es 7.4. |
| Doctrine ORM | 3.7.5 | Versión instalada según `composer.lock`. |
| PHPUnit | 13.4.5 | Versión instalada según `composer.lock`. |

Las versiones instaladas en una máquina pueden variar si se reconstruyen imágenes basadas en etiquetas mutables, especialmente `alpine`, o si se actualiza `composer.lock`. Para conocer las versiones efectivas, consulta la sección [Verificar versiones](#verificar-versiones).

## 1. Obtener el proyecto

Si todavía no tienes una copia local:

```bash
git clone <URL_DEL_REPOSITORIO>
cd <DIRECTORIO_CLONADO>
```

Si ya tienes el repositorio, entra en su directorio raíz.

## 2. Revisar la configuración local

La configuración de base de datos se lee de `.env` y puede sobrescribirse en `.env.local`, que no se debe subir al repositorio. La conexión del contenedor PHP debe usar el hostname interno `db` y el puerto `5432`; no uses `localhost` desde el contenedor.

Confirma que la variable `DATABASE_URL` corresponda a tu entorno local y a la base `faccob`. No publiques ni incluyas credenciales reales en documentación o capturas. El `Makefile` pasa el identificador de usuario al construir el contenedor PHP.

## 4. Construir e iniciar los contenedores

```bash
make build
make up
```

Estos objetivos del `Makefile` llaman a `docker-compose build` y `docker-compose up -d`, respectivamente. El archivo `docker-compose.yml` inicia estos servicios:

| Servicio | Función | Acceso |
|---|---|---|
| `node-fac-cob` | PHP-FPM y aplicación Symfony. | Interno en la red Docker. |
| `faccob-web` | Servidor web Nginx. | `http://localhost:8008` |
| `db` | Base de datos PostgreSQL. | Puerto `5439` del host; dentro de Docker, `db:5432`. |
| `mailer` | Mailcatcher para desarrollo. | Interfaz en `http://localhost:1081`; SMTP en el puerto `1026` del host. |

Verifica que los servicios estén activos y que PostgreSQL acepte conexiones:

```bash
docker-compose ps
docker exec faccob-postgres pg_isready -U faccob -d faccob
```

## 5. Instalar dependencias PHP

El objetivo existente instala las dependencias definidas en `composer.lock`:

```bash
make composer-install-nointeraction
```

## 6. Crear o actualizar el esquema

El `Makefile` no define un objetivo seguro para ejecutar migraciones Doctrine. Entra al contenedor con el objetivo `bash` y ejecuta la migración manualmente:

```bash
make bash
php bin/console doctrine:migrations:migrate --no-interaction
exit
```

No ejecutes este comando contra una base de producción. Revisa la conexión antes de migrar.
No uses `make ssh-schema-update` para este paso: ese objetivo ejecuta `doctrine-drop-update-fill.sh`, que puede eliminar y volver a cargar el esquema y los datos.

## 7. Abrir la aplicación

Abre [http://localhost:8008](http://localhost:8008).

Comandos útiles:

```bash
# Ver estado
docker-compose ps

# Seguir los logs de todos los servicios
docker-compose logs -f

# Abrir una shell en PHP (objetivo del Makefile)
make bash

# Ejecutar las pruebas dentro de esa shell
php bin/phpunit
```

Para apagar los servicios conservando los datos:

```bash
make stop
```

El objetivo `stop` detiene los servicios sin eliminar contenedores, volúmenes ni datos. El `Makefile` no define un objetivo `down`.

## Entorno aislado para demostración

El demo debe usar una base separada (`faccob_demo`), no la base normal `faccob` ni producción. Su configuración Compose utiliza la red e imagen del stack principal; inicia primero el proyecto con los pasos anteriores.

La configuración de demo debe estar disponible como `docker-compose.demo.yml` en la raíz y debe incluir `docker/nginx/demo.conf`. **Si esos archivos no aparecen en tu copia, no ejecutes los comandos siguientes hasta obtenerlos**: este documento no crea ni modifica esa configuración. El `Makefile` mostrado no tiene objetivos para este stack separado, por lo que estos pasos usan `docker-compose` directamente.

1. Crea la base una sola vez:

   ```bash
   docker exec faccob-postgres createdb -U faccob faccob_demo
   ```

   Si ya existe, no repitas este paso.

2. En la terminal, define `DEMO_DATABASE_URL` usando las credenciales locales de PostgreSQL y el hostname interno `db`, el puerto `5432` y la base `faccob_demo`. No guardes esa URL en el repositorio:

   ```bash
   export DEMO_DATABASE_URL='postgresql://USUARIO:CONTRASEÑA@db:5432/faccob_demo?serverVersion=16&charset=utf8'
   ```

3. Ejecuta las migraciones y prepara el escenario:

   ```bash
   docker compose -p nodo-demo -f docker-compose.demo.yml run --rm demo-php php bin/console doctrine:migrations:migrate --no-interaction
   docker compose -p nodo-demo -f docker-compose.demo.yml run --rm demo-php php bin/console app:demo:preparar-acceso
   ```

   La preparación del escenario está diseñada para ser idempotente mientras permanezca en su estado inicial. No la uses como mecanismo para reiniciar una demo que ya avanzó.

4. Inicia el sitio de demostración:

   ```bash
   docker compose -p nodo-demo -f docker-compose.demo.yml up -d demo-php demo-web
   ```

   Abre [http://localhost:8009/accesos/1](http://localhost:8009/accesos/1). Los resultados de pago, aviso, huella y la espera de 24 horas son simulados; no afirmes que ocurrieron en tiempo real.

5. Detén la web de demo conservando la base y el escenario:

   ```bash
   docker compose -p nodo-demo -f docker-compose.demo.yml down
   ```

## Verificar versiones

Para consultar las herramientas del host:

```bash
docker --version
docker-compose --version
docker compose version
```

El `Makefile` no incluye objetivos para consultar versiones; utiliza su objetivo `bash` para entrar en PHP y ejecuta allí los comandos correspondientes:

```bash
make bash
php -v
composer --version
php bin/console --version
php bin/phpunit --version
exit
```

Para PostgreSQL y Nginx, que no tienen objetivos de verificación en el `Makefile`, consulta:

```bash
docker exec faccob-postgres psql --version
docker exec faccob-web nginx -v
```

Las versiones de Symfony, Doctrine y PHPUnit instaladas se controlan mediante `composer.lock`. Las versiones de Docker y de las imágenes pueden cambiar según el equipo y cuándo se construyeron.
