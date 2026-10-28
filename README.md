# App Tareas

Aplicación de autenticación desarrollada con Laravel para el proyecto de Ingeniería de Software de Uniempresarial. Incluye el flujo web con validaciones en vivo y una API REST protegida con tokens.

## Estado del proyecto

| Historia | Descripción | Estado |
| --- | --- | --- |
| HU-01 | Registro de usuarios con validaciones y contraseña hasheada | Implementado |
| HU-02 | Inicio de sesión con mensaje genérico y acceso al panel | Implementado |
| HU-04 | Cierre de sesión y protección del panel al volver atrás | Implementado |
| HU-03 | Recuperación de contraseña | Pendiente |
| HU-05 | Límite de intentos fallidos | Pendiente |

Además:

- API REST de autenticación con Laravel Sanctum.
- Validación en vivo de correo, contraseña y confirmación.
- Mensajes de éxito al registrar y al cerrar sesión.
- 32 pruebas automatizadas.

## Stack

- PHP 8.4.1+ (probado con PHP 8.4 y 8.5)
- Laravel 13
- PostgreSQL 18
- Laravel Sanctum 4
- Vite 8 + Tailwind CSS 4
- PHPUnit 12
- Docker y Docker Compose

## Requisitos

- PHP 8.4.1 o superior con las extensiones `pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`, `zip` e `intl`.
- Composer 2.
- Node.js 22.12 o superior y npm (probado con Node 24).
- PostgreSQL 15 o superior (solo para la aplicación; las pruebas usan SQLite en memoria).

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
```

En Windows PowerShell:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Configura la base de datos en `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bd
DB_USERNAME=postgres
DB_PASSWORD=tu_clave_local
```

Crea la base si no existe y ejecuta las migraciones:

```bash
createdb -U postgres bd
php artisan migrate
```

También puedes crearla desde `psql`:

```sql
CREATE DATABASE bd;
```

Instala y compila los assets, y levanta el servidor:

```bash
npm install
npm run build
php artisan serve
```

La aplicación queda disponible en <http://localhost:8000>.

> Las pruebas usan SQLite en memoria (`phpunit.xml`), por lo que no necesitan PostgreSQL.

## Base de datos

- Entorno local: base `bd`, usuario `postgres`. La contraseña se define únicamente en el archivo `.env` local, que no se versiona.
- Entorno Docker: el servicio `db` crea la base y el usuario a partir de `POSTGRES_DB`, `POSTGRES_USER` y `POSTGRES_PASSWORD` definidos en `.env`. La aplicación se conecta con `DB_HOST=db`.
- Los datos de PostgreSQL se conservan en el volumen `pgdata`.

## Docker

1. Crea el archivo de entorno de producción:

   ```bash
   cp .env.production.example .env
   ```

2. Edita `.env` y define como mínimo:

   - `APP_URL` con la URL o IP del servidor.
   - `DB_PASSWORD` con una clave segura.
   - `APP_KEY` con una clave generada en el host. El valor debe empezar por `base64:`:

     ```bash
     echo "base64:$(openssl rand -base64 32)"
     ```

     Si prefieres usar PHP:

     ```bash
     php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
     ```

3. Construye y levanta los contenedores:

   ```bash
   docker compose -f docker-compose.prod.yml up -d --build
   ```

   El contenedor `app` ejecuta `php artisan migrate --force` antes de iniciar el servidor.

4. Verifica el estado:

   ```bash
   docker compose -f docker-compose.prod.yml ps
   curl -I http://localhost
   ```

La aplicación se publica en el puerto `80`. La guía del servidor Oracle está en [`despliegue.md`](despliegue.md).

Comandos útiles:

```bash
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml down
```

Para actualizar una instancia existente:

```bash
git pull
docker compose -f docker-compose.prod.yml up -d --build
```

> Si cambias el nombre de la base después de crear el volumen `pgdata`, PostgreSQL no crea la nueva base automáticamente. Crea la base manualmente o elimina el volumen con `docker compose -f docker-compose.prod.yml down -v` (esto borra los datos).

## API REST

La API usa tokens Bearer de Sanctum. No usa sesiones ni CSRF.

| Método | Ruta | Autenticación | Descripción |
| --- | --- | --- | --- |
| POST | `/api/register` | No | Registra un usuario y devuelve un token |
| POST | `/api/login` | No | Inicia sesión y devuelve un token |
| GET | `/api/me` | Bearer | Devuelve el usuario autenticado |
| POST | `/api/logout` | Bearer | Revoca el token actual |

### Registro

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ana Torres",
    "email": "ana@test.local",
    "password": "Abcd1234",
    "password_confirmation": "Abcd1234"
  }'
```

Respuesta `201 Created`:

```json
{
  "message": "Cuenta creada correctamente.",
  "user": {
    "id": 1,
    "name": "Ana Torres",
    "email": "ana@test.local"
  },
  "token": "1|token-generado",
  "token_type": "Bearer"
}
```

### Inicio de sesión

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "ana@test.local", "password": "Abcd1234"}'
```

Respuesta `200 OK` con la misma estructura del registro. Si las credenciales fallan, responde `422` con el mensaje genérico en `errors.email`.

### Perfil y cierre de sesión

```bash
curl http://localhost:8000/api/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN"

curl -X POST http://localhost:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN"
```

Sin token, `/api/me` y `/api/logout` responden `401 Unauthorized`.

## Flujo web

- `GET /registro`: formulario de creación de cuenta con validación en vivo.
- `POST /registro`: crea el usuario y redirige a `/login` con el mensaje “Cuenta creada correctamente. Inicia sesión.”.
- `GET /login`: formulario de acceso con validación en vivo.
- `POST /login`: autentica y redirige a `/panel`.
- `GET /panel`: área privada protegida por el middleware `auth`, con botón para cerrar sesión.
- `POST /logout`: invalida la sesión, regenera el token CSRF y redirige a `/login`.
- El panel se envía con cabeceras `no-store` y se recarga si el navegador lo restaura desde la caché de historial. Después de cerrar sesión, el botón Atrás o una URL directa a `/panel` redirigen al login.

## Pruebas

```bash
php artisan test
```

La suite incluye los casos CP-01 a CP-14 del plan de pruebas, el flujo de cierre de sesión y la protección del panel. Al momento de escribir este README: **32 pruebas y 119 aserciones**.

También puedes ejecutar:

```bash
vendor/bin/pint
vendor/bin/pint --test
```

El flujo de integración continua está en [`.github/workflows/tests.yml`](.github/workflows/tests.yml) y se ejecuta en cada push a `main`, `Backend`, `Frontend` y `Experimentar`, y en pull requests a `main`.

## Estructura relevante

```text
app/
  Http/Controllers/
    Api/AuthController.php       API de registro, login, perfil y logout
    LoginController.php          Login web y cierre de sesión
    RegisterController.php       Registro web
  Http/Middleware/
    PreventBackHistory.php       Cabeceras para evitar el botón Atrás
  Models/User.php                Modelo con tokens de Sanctum
config/
  sanctum.php                    Configuración de Sanctum
database/
  migrations/                    Tablas de usuarios, sesiones y tokens
resources/
  js/validation.js               Validación en vivo del formulario
  views/                         Login, registro y panel
routes/
  api.php                        Endpoints /api/*
  web.php                        Rutas web
tests/Feature/
  Api/AuthApiTest.php            Pruebas de la API
  AuthenticationComplianceTest.php  Pruebas de aceptación web
documentacion/
  Product & Sprint Backlog.xlsx  Backlog, plan de pruebas y KPI
```

## Ramas de trabajo

- `Backend`: cambios de API y lógica del servidor.
- `Frontend`: cambios de interfaz, validaciones y flujo web.
- `main`: rama de integración; recibe los merges de `Backend` y `Frontend`.

## Documentación

- [`documentacion/Product & Sprint Backlog.xlsx`](documentacion/Product%20%26%20Sprint%20Backlog.xlsx): historias de usuario, criterios de aceptación, plan de pruebas y KPI.
- [`despliegue.md`](despliegue.md): despliegue en Oracle Cloud con Docker.

## Licencia

Proyecto académico distribuido bajo licencia MIT.
