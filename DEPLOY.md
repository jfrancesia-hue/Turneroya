# TurneroYa · Guía de Deploy

Esta guía cubre 3 opciones: **Render (recomendado)**, **Hostinger VPS** y **desarrollo local con Docker**.

---

## Opción 1: Render (recomendado) — 15 minutos

Render soporta Docker nativamente, Postgres administrado y cron jobs. El `render.yaml` en la raíz del repo configura todo.

### Paso 1 — Crear el blueprint
1. Ir a https://dashboard.render.com/blueprints
2. **New Blueprint Instance** → conectar GitHub → elegir `jfrancesia-hue/Turneroya` → rama `main`.
3. Render detecta `render.yaml` y propone:
   - Web Service: `turneroya-app` ($7/mo)
   - Database: `turneroya-db` Postgres 16 ($7/mo, 256MB RAM, 1GB storage)
   - Cron Job: `turneroya-reminders` (cada hora)
4. **Apply** — el primer build tarda 3-5 minutos.

### Paso 2 — Cargar secretos (Dashboard → turneroya-app → Environment)
Las variables marcadas `sync: false` deben cargarse a mano:

| Variable | Dónde sacarla |
|---|---|
| `APP_URL` | `https://turneroya-app.onrender.com` (o tu dominio) |
| `ANTHROPIC_API_KEY` | console.anthropic.com → API Keys |
| `TWILIO_ACCOUNT_SID` | console.twilio.com → Account Info |
| `TWILIO_AUTH_TOKEN` | console.twilio.com → Account Info |
| `TWILIO_WHATSAPP_FROM` | `whatsapp:+14155238886` (sandbox) o tu número aprobado |
| `MERCADOPAGO_ACCESS_TOKEN` | mercadopago.com.ar/developers → Credenciales de producción |
| `MERCADOPAGO_PUBLIC_KEY` | idem |
| `MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` | SMTP (Resend/Mailgun/Gmail) |

### Paso 3 — Migraciones
El `entrypoint.sh` corre `php scripts/migrate.php` automáticamente en cada deploy (idempotente). Al finalizar el primer deploy ya tenés la DB con schema + plans seedados.

Para datos demo opcionales (Nativos Consultora + turnos de ejemplo):
```bash
# Desde Render Shell (botón "Shell" en el dashboard del servicio)
php scripts/seed.php
```

### Paso 4 — Configurar dominio
1. Dashboard → `turneroya-app` → **Settings** → **Custom Domains** → agregar `turneroya.app` y `www.turneroya.app`.
2. Agregar los DNS records (CNAME o ALIAS) que indica Render en tu proveedor de dominio.
3. SSL se provisiona automático (Let's Encrypt).
4. Actualizar `APP_URL` en Environment con el dominio final.

### Paso 5 — Webhooks de Twilio y MercadoPago
**Twilio WhatsApp (Sandbox o Production):**
- URL: `https://turneroya.app/api/webhook/whatsapp`
- Method: `POST`
- Configurar en: console.twilio.com → Messaging → Settings → WhatsApp Sandbox (o Sender) → When a message comes in

**MercadoPago (Suscripciones + Señas):**
- URL: `https://turneroya.app/api/webhook/mercadopago`
- Configurar en: mercadopago.com.ar/developers → Tu App → Webhooks → Eventos: `payment`, `subscription_preapproval`, `subscription_authorized_payment`

### Paso 6 — Verificar
- `https://turneroya.app` → landing
- `https://turneroya.app/pricing` → planes
- `https://turneroya.app/register` → crear cuenta
- `https://turneroya.app/dashboard/billing` → contratar plan (redirige a MP)

---

## Opción 2: Hostinger VPS ($5/mes) — 45 minutos

### Setup inicial
```bash
# En el VPS (Ubuntu 22.04)
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-curl \
    php8.2-zip php8.2-xml composer postgresql postgresql-contrib git certbot python3-certbot-nginx

# Postgres
sudo -u postgres createuser --interactive  # crear user "turneroya"
sudo -u postgres createdb -O turneroya turneroya
sudo -u postgres psql -c "ALTER USER turneroya WITH ENCRYPTED PASSWORD 'XXX';"

# Clonar y configurar
sudo mkdir -p /var/www/turneroya
sudo chown -R $USER:www-data /var/www/turneroya
cd /var/www/turneroya
git clone https://github.com/jfrancesia-hue/Turneroya.git .
cp .env.example .env
nano .env   # cargar todos los secretos

composer install --no-dev --optimize-autoloader
php scripts/migrate.php
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

### Nginx
```nginx
# /etc/nginx/sites-available/turneroya
server {
    listen 80;
    server_name turneroya.app www.turneroya.app;
    root /var/www/turneroya/public;
    index index.php;
    client_max_body_size 20m;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    location ~ /\.(env|git) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/turneroya /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d turneroya.app -d www.turneroya.app
```

### Cron de recordatorios
```bash
sudo crontab -u www-data -e
# Añadir:
0 * * * * cd /var/www/turneroya && php scripts/reminders_cron.php >> storage/logs/reminders.log 2>&1
```

---

## Opción 3: Desarrollo local con Docker

```bash
docker compose up -d
# App: http://localhost:8080
# DB: localhost:5432 (user: postgres / pass: postgres)

docker compose exec app php scripts/migrate.php
docker compose exec app php scripts/seed.php   # datos demo
```

---

## Checklist post-deploy

- [ ] `/` carga y muestra landing
- [ ] `/pricing` muestra 4 planes
- [ ] `/register` crea usuario + onboarding funciona
- [ ] `/dashboard/billing` redirige a MercadoPago al contratar
- [ ] Webhook Twilio apunta a la URL correcta (test enviando msg al sandbox)
- [ ] Webhook MP configurado con eventos `subscription_preapproval` + `subscription_authorized_payment`
- [ ] SSL válido (candado verde)
- [ ] Cron de recordatorios se ejecutó al menos una vez (ver `storage/logs/reminders.log`)
- [ ] `/terms` y `/privacy` cargan

## Monitoreo sugerido

- **Uptime:** UptimeRobot free (5min checks)
- **Errores:** revisar logs de Render / `storage/logs/`
- **DB:** Render Dashboard → Metrics (conexiones, CPU, storage)
- **Costos MP:** mercadopago.com.ar → Ventas → Suscripciones
