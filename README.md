# App Tareas

Monorepo de autenticación para el proyecto de Ingeniería de Software de Uniempresarial. Está compuesto por dos aplicaciones Laravel conectadas por una API REST:

- **backend/**: API Laravel con Sanctum, PostgreSQL y toda la lógica de usuarios.
- **frontend/**: aplicación Laravel con Blade que consume la API y guarda el token en la sesión del servidor.

## Roles

| Área | Integrantes | GitHub |
| --- | --- | --- |
| Backend y BD | Samuel Prieto, Daniela Ramírez | `@iNxTSam`, `@Dani34881` |
| Frontend | Anderson Lugo, Nicolás Arenas | `@Lugop67`, `@Estebaaaannxx11` |
| Pruebas y UI | Jose Daniel | `@JDDAR` |

Jose Daniel tiene la invitación pendiente; mientras tanto, las revisiones de QA/docs las respalda `@iNxTSam`.

El navegador nunca recibe el token directamente: el frontend actúa como BFF (Backend For Frontend), llama a la API por HTTP y conserva el token en la sesión de Laravel.

## Arquitectura

```text
Navegador
   │  sesión + CSRF
   ▼
frontend/ (Laravel + Blade)  ──HTTP + Bearer token──►  backend/ (Laravel API)
                                                          │
                                                          ▼
                                                     PostgreSQL
```

- **Backend**: único dueño de la base de datos, usuarios, migraciones y tokens de Sanctum.
- **Frontend**: no tiene base de datos ni credenciales de PostgreSQL; solo sesión, vistas y llamadas HTTP a la API.
- **Docker**: el frontend es el único servicio público. El backend y PostgreSQL quedan en redes internas.

## Estructura

```text
appTareas_Final/
├── backend/                       API Laravel
│   ├── app/Http/Controllers/Api/  Controlador de autenticación
│   ├── app/Models/User.php
│   ├── routes/api.php
│   ├── database/migrations/
│   ├── tests/Feature/Api/
│   ├── config/sanctum.php
│   ├── .env.example
│   └── Dockerfile
├── frontend/                      Laravel + Blade
│   ├── app/Http/Controllers/Web/  Login, registro y panel
│   ├── app/Http/Middleware/Web/   Autenticación y protección Atrás
│   ├── app/Services/BackendApiClient.php
│   ├── resources/views/
│   ├── resources/css/app.css
│   ├── resources/js/
│   ├── routes/web.php
│   ├── tests/Feature/Web/
│   ├── .env.example
│   └── Dockerfile
├── docs/
│   ├── Product & Sprint Backlog.xlsx
│   └── despliegue.md
├── .github/
│   ├── CODEOWNERS
│   ├── pull_request_template.md
│   ├── ISSUE_TEMPLATE/
│   └── workflows/tests.yml
├── compose.yaml
├── package.json                   Orquestación backend + frontend
├── .env.example                   Variables de Compose
├── CONTRIBUTING.md
├── README.md
├── AGENTS.md
└── CLAUDE.md
```

## Stack

- PHP 8.4.1+ (probado con PHP 8.4 y 8.5)
- Laravel 13
- PostgreSQL 18
- Laravel Sanctum 4
- Vite 8 + Tailwind CSS 4
- PHPUnit 12
- Docker y Docker Compose

## Requisitos

- PHP 8.4.1 o superior con `pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`, `zip` e `intl`.
- Composer 2.
- Node.js 22.12 o superior y npm (probado con Node 24).
- PostgreSQL 15 o superior.

## Instalación local

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configura la base en `backend/.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bd
DB_USERNAME=postgres
DB_PASSWORD=tu_clave_local
```

Crea la base y ejecuta las migraciones:

```bash
createdb -U postgres bd
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8001
```

La API queda en <http://127.0.0.1:8001>. La raíz responde con información básica y `/up` es el healthcheck.

### Frontend

```bash
cd frontend
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

En `frontend/.env` configura la URL del backend:

```dotenv
BACKEND_API_URL=http://127.0.0.1:8001
```

La aplicación queda en <http://127.0.0.1:8000>. El frontend usa `SESSION_DRIVER=file` y `CACHE_STORE=file`; no necesita PostgreSQL.

> Las pruebas usan SQLite en memoria y `Http::fake()`. No requieren PostgreSQL ni que el backend esté levantado.

## Desarrollo en una sola terminal

Desde la raíz del repositorio:

```bash
npm run setup   # primera vez: instala dependencias de backend y frontend
npm run dev
```

`npm run dev` levanta:

- Backend en `http://127.0.0.1:8001`.
- Frontend en `http://127.0.0.1:8000`.
- Vite en `http://127.0.0.1:5173` para recarga en caliente.

Para usar los assets compilados sin Vite:

```bash
npm run start   # compila el frontend y levanta backend + frontend
```

Otros comandos:

```bash
npm run build       # compila el frontend
npm run test        # ejecuta pruebas de backend y frontend
npm run docker:up   # docker compose -f compose.yaml up -d --build
npm run docker:down # docker compose -f compose.yaml down
```

Detén todo con `Ctrl+C`.

## Docker

1. Crea los archivos de entorno:

   ```bash
   cp .env.example .env
   cp backend/.env.production.example backend/.env
   cp frontend/.env.production.example frontend/.env
   ```

2. Genera una `APP_KEY` distinta para cada aplicación:

   ```bash
   echo "base64:$(openssl rand -base64 32)"
   ```

   Pega el resultado en `backend/.env` y en `frontend/.env`, cada uno con su propia clave.

3. Edita:

   - `.env` (raíz): `DB_PASSWORD` y, si aplica, `FRONTEND_PORT`.
   - `backend/.env`: `APP_KEY`, `DB_PASSWORD` (opcional; Compose lo toma de la raíz).
   - `frontend/.env`: `APP_KEY`, `APP_URL` con la URL o IP pública.

4. Construye y levanta:

   ```bash
   docker compose -f compose.yaml up -d --build
   ```

   El backend ejecuta `php artisan migrate --force` al iniciar. El frontend espera a que el backend esté saludable.

5. Verifica:

   ```bash
   docker compose -f compose.yaml ps
   curl -I http://localhost
   ```

   El frontend se publica en el puerto `80`; el backend queda en la red interna como `http://backend:8000` y PostgreSQL como `db`.

Comandos útiles:

```bash
docker compose -f compose.yaml logs -f frontend
docker compose -f compose.yaml logs -f backend
docker compose -f compose.yaml exec backend php artisan migrate --force
docker compose -f compose.yaml down
```

Para actualizar:

```bash
git pull
docker compose -f compose.yaml up -d --build
```

> Si cambias el nombre de la base después de crear el volumen `pgdata`, PostgreSQL no crea la nueva base automáticamente. Créala manualmente o elimina el volumen con `docker compose -f compose.yaml down -v` (esto borra los datos).

## API REST

La API usa tokens Bearer de Sanctum. La autenticación de la API es independiente de la sesión del frontend.

| Método | Ruta | Autenticación | Descripción |
| --- | --- | --- | --- |
| POST | `/api/register` | No | Registra un usuario y devuelve un token |
| POST | `/api/login` | No | Inicia sesión y devuelve un token |
| GET | `/api/me` | Bearer | Devuelve el usuario autenticado |
| POST | `/api/logout` | Bearer | Revoca el token actual |

### Registro

```bash
curl -X POST http://127.0.0.1:8001/api/register \
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
curl -X POST http://127.0.0.1:8001/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "ana@test.local", "password": "Abcd1234"}'
```

Respuesta `200 OK` con la misma estructura del registro. Si las credenciales fallan, responde `422` con el mensaje genérico en `errors.email`.

### Perfil y cierre de sesión

```bash
curl http://127.0.0.1:8001/api/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN"

curl -X POST http://127.0.0.1:8001/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN"
```

Sin token, `/api/me` y `/api/logout` responden `401 Unauthorized`.

## Flujo web

- `GET /registro`: formulario de creación de cuenta con validación en vivo.
- `POST /registro`: el frontend valida y llama a `POST /api/register`; redirige a `/login` con “Cuenta creada correctamente. Inicia sesión.”.
- `GET /login`: formulario de acceso.
- `POST /login`: el frontend llama a `POST /api/login`, guarda el token en la sesión y redirige a `/panel`.
- `GET /panel`: exige token en sesión, valida con `GET /api/me` y muestra el área privada.
- `POST /logout`: revoca el token en la API, limpia la sesión y redirige al login.
- El panel se envía con cabeceras `no-store` y se recarga si el navegador lo restaura desde la caché de historial. Después de cerrar sesión, el botón Atrás o una URL directa a `/panel` redirigen al login.

## Pruebas

Backend:

```bash
cd backend
php artisan test
```

Frontend:

```bash
cd frontend
php artisan test
```

La suite actual suma **32 pruebas**: 14 del backend (API y hash de contraseña) y 18 del frontend (flujo Blade con la API simulada).

También puedes ejecutar el formateador desde cada aplicación:

```bash
cd backend && vendor/bin/pint
cd frontend && vendor/bin/pint
```

El flujo de integración continua está en [`.github/workflows/tests.yml`](.github/workflows/tests.yml) con dos jobs: backend y frontend. Se ejecuta en push a `main`, `backend`, `frontend` y `testing`, y en pull requests hacia esas ramas.

## Ramas de trabajo

Trabajamos con una rama por rol y `main` como integración:

- `backend`: equipo Backend/BD; API, modelos, migraciones y pruebas de API.
- `frontend`: equipo Frontend; vistas Blade, assets y pruebas web.
- `testing`: QA/UI; pruebas, documentación y validación visual.
- `main`: rama estable y protegida; recibe los PR de integración.
- Cada integrante puede trabajar en su rama de rol o en una subrama `backend/<tarea>`, `frontend/<tarea>` o `testing/<tarea>`.
- Los PR hacia `main` requieren al menos una aprobación, CI en verde y squash merge.
- `CODEOWNERS` asigna la revisión según la carpeta y el rol.
- Las antiguas ramas `Backend`, `Frontend` y `Experimentar` quedaron archivadas en los tags `archive/*`.

## Documentación

- [`docs/Product & Sprint Backlog.xlsx`](docs/Product%20%26%20Sprint%20Backlog.xlsx): historias de usuario, criterios de aceptación, plan de pruebas y KPI.
- [`docs/despliegue.md`](docs/despliegue.md): despliegue en Oracle Cloud con Docker.
- [`CONTRIBUTING.md`](CONTRIBUTING.md): roles, ramas, commits y revisión.

## Seguridad

- El token de Sanctum se guarda en la sesión del frontend; no se expone a JavaScript ni a `localStorage`.
- Backend y frontend usan `APP_KEY` diferentes.
- El frontend no tiene credenciales de PostgreSQL.
- En producción con HTTPS, activa `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true`.
- Cambia la contraseña de PostgreSQL y las claves de ejemplo antes de desplegar.

## Licencia

Proyecto académico distribuido bajo licencia MIT.
