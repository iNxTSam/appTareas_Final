# Despliegue en Oracle Cloud

Instancia: `servidor-web-2` — Oracle Linux 9.8 — IP `157.137.210.124`.

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

## 3. Clonar y desplegar

```sh
git clone https://github.com/iNxTSam/appTareas_Final.git
cd appTareas_Final
cp .env.production.example .env
nano .env      # edita DB_PASSWORD
docker compose -f docker-compose.prod.yml up -d --build
```

Generar la APP_KEY (una sola vez):

```sh
docker compose -f docker-compose.prod.yml exec app php artisan key:generate
docker compose -f docker-compose.prod.yml restart app
```

## 4. Verificar

```sh
docker compose -f docker-compose.prod.yml ps
curl -I http://localhost/login
```

Abrir en el navegador: `http://157.137.210.124/login`

## 5. Actualizar la app

```sh
git pull
docker compose -f docker-compose.prod.yml up -d --build
```

## Comandos útiles

```sh
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml down
```
