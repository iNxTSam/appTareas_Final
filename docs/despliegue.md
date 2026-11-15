# Despliegue en Oracle Cloud

Instancia: `servidor-web-2` — Oracle Linux 9.8 — IP `157.137.210.124`.

La aplicación se despliega como tres servicios:

- `frontend`: Laravel + Blade, público en el puerto 80.
- `backend`: Laravel API, solo accesible desde la red interna de Docker.
- `db`: PostgreSQL 18, solo accesible desde el backend.

## 1. Requisitos en el servidor

```sh
sudo dnf -y install dnf-utils
sudo dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
sudo dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo usermod -aG docker opc
```

## 2. Abrir puertos en la VCN

En la consola de Oracle: Networking > Virtual Cloud Networks > Security List >
Add Ingress Rules:

- TCP puerto **80** desde `0.0.0.0/0`
- TCP puerto **443** desde `0.0.0.0/0`

## 3. Clonar y configurar

```sh
git clone https://github.com/iNxTSam/appTareas_Final.git
cd appTareas_Final

cp .env.example .env
cp backend/.env.production.example backend/.env
cp frontend/.env.production.example frontend/.env
```

Genera una clave distinta para cada aplicación:

```sh
echo "base64:$(openssl rand -base64 32)"
```

Pega la primera en `APP_KEY` de `backend/.env` y la segunda en `APP_KEY` de `frontend/.env`.

Edita:

- `.env` (raíz): `DB_PASSWORD`, `FRONTEND_PORT` y `FRONTEND_APP_URL` con la IP o dominio público.
- `backend/.env`: `APP_KEY` y las variables `MAIL_*` de la sección de correo.
- `frontend/.env`: `APP_KEY`; `APP_URL` se sobrescribe con `FRONTEND_APP_URL` en Compose.

### Correo

La recuperación de contraseña necesita un correo real. Si dejas
`MAIL_MAILER=log`, el enlace se escribe en `storage/logs/laravel.log` y el
usuario nunca lo recibe.

El proyecto usa la cuenta de Gmail `app.tareas.noreply@gmail.com` con una
contraseña de aplicación. En `backend/.env` define:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=app.tareas.noreply@gmail.com
MAIL_PASSWORD=la_contrasena_de_aplicacion
MAIL_FROM_ADDRESS="app.tareas.noreply@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

La contraseña de aplicación se genera en la cuenta de Google (Seguridad >
Verificación en dos pasos > Contraseñas de aplicaciones). No es la contraseña
normal de Gmail y no debe subirse al repositorio: `backend/.env` está ignorado
por Git.

Comprueba el envío con un correo real:

```bash
docker compose -f compose.yaml exec backend php artisan tinker --execute="Mail::raw('Prueba de correo', function (\$m) { \$m->to('destino@ejemplo.com')->subject('Prueba App Tareas'); });"
```

Si el comando termina sin error, el SMTP quedó configurado. Revisa también la
bandeja de spam.

## 4. Construir y levantar

```sh
docker compose -f compose.yaml up -d --build
```

El contenedor `backend` ejecuta `php artisan migrate --force` al iniciar. El
frontend espera a que el backend esté saludable antes de arrancar.

## 5. Verificar

```sh
docker compose -f compose.yaml ps
curl -I http://localhost
curl -I http://localhost/up
```

Abrir en el navegador: `http://157.137.210.124`.

El backend no se publica directamente; el frontend lo consume como
`http://backend:8000` dentro de la red `frontend_net`.

## 6. Actualizar la app

```sh
git pull
docker compose -f compose.yaml up -d --build
```

## Comandos útiles

```sh
docker compose -f compose.yaml logs -f frontend
docker compose -f compose.yaml logs -f backend
docker compose -f compose.yaml exec backend php artisan migrate --force
docker compose -f compose.yaml exec backend php artisan route:list
docker compose -f compose.yaml down
```

> Si el volumen `pgdata` ya existe con otra base, PostgreSQL no crea la nueva
> automáticamente. Créala manualmente o elimina el volumen con
> `docker compose -f compose.yaml down -v` (esto borra los datos).
