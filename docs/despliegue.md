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

- `.env` (raíz): `DB_PASSWORD` y, si aplica, `FRONTEND_PORT`.
- `frontend/.env`: `APP_URL` con la IP o dominio público.

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
