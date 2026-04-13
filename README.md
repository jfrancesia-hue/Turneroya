# TurneroYa

**SaaS de gestión de turnos con IA por WhatsApp.** Panel de administración, página pública de reservas, calendario inteligente, recordatorios automáticos y chatbot alimentado por **Claude (Anthropic)** que toma turnos por WhatsApp.

Desarrollado en **PHP 8.2+ puro** (sin frameworks) + **PostgreSQL**.

---

## Stack

- **Backend:** PHP 8.2+ puro con arquitectura propia (Core: App/Router/Session/Auth/Validator/View)
- **DB:** PostgreSQL 14+ via PDO
- **Frontend:** Tailwind CSS (CDN) + Alpine.js + FullCalendar + Chart.js
- **IA:** Claude (Anthropic) via API HTTP (Haiku 4.5 para bot, tool use / function calling)
- **WhatsApp:** Twilio WhatsApp Business API
- **Pagos:** MercadoPago SDK (depósitos/señas)
- **Emails:** PHPMailer / SMTP
- **Timezone:** `America/Argentina/Buenos_Aires`

## Estructura

```
TurneroYa/
├── public/
│   ├── index.php            # Front controller
│   ├── .htaccess            # Rewrite a index.php
│   └── assets/
├── src/
│   ├── Core/                # App, Router, Database, Session, Auth, View, Validator
│   ├── Models/              # User, Business, Professional, Service, Schedule,
│   │                        # Blockout, Client, Booking, BotConversation
│   ├── Controllers/         # Auth, Dashboard, Professional, Service, Schedule,
│   │                        # Blockout, Client, Booking, Calendar, Analytics,
│   │                        # PublicBooking, Webhook, Cron, Bot, Settings
│   ├── Services/            # SlotCalculator ⭐, BookingService, BotEngine,
│   │                        # ClaudeClient, NotificationService, MercadoPagoService
│   ├── Middleware/          # Auth, Guest, Csrf
│   ├── Views/               # Layouts + vistas PHP puras
│   └── Helpers/functions.php
├── config/                  # app, database, services, routes
├── database/migrations/     # SQL migraciones
├── scripts/                 # migrate, seed, reminders_cron
├── storage/                 # logs, sessions, uploads
├── composer.json
└── .env.example
```

## Instalación

### 1. Requisitos
- PHP 8.2+ con extensiones: `pdo_pgsql`, `curl`, `mbstring`, `json`
- PostgreSQL 14+
- Composer 2.x

### 2. Clonar y dependencias
```bash
cd E:/Usuario/TurneroYa
composer install
cp .env.example .env
```

### 3. Configurar `.env`
```ini
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=turneroya
DB_USERNAME=postgres
DB_PASSWORD=tu_password

ANTHROPIC_API_KEY=sk-ant-xxxx
ANTHROPIC_MODEL=claude-haiku-4-5-20251001

TWILIO_ACCOUNT_SID=ACxxxx
TWILIO_AUTH_TOKEN=xxxx
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886

MERCADOPAGO_ACCESS_TOKEN=TEST-xxxx
CRON_SECRET=un-token-secreto-largo
```

### 4. Crear DB y migrar
```bash
createdb turneroya
composer migrate
```

### 5. Cargar datos de ejemplo (opcional)
```bash
composer seed
```

Credenciales demo:
- **Email:** `admin@demo.com`
- **Password:** `demo1234`
- **Dashboard:** http://localhost:8000/login
- **Página pública:** http://localhost:8000/book/belleza-pura

### 6. Correr en local
```bash
composer serve
# o
php -S localhost:8000 -t public
```

## Funcionalidades

### Panel de administración
- **Dashboard**: resumen, próximos turnos, link público
- **Calendario**: vista semanal/diaria con FullCalendar
- **Turnos**: CRUD completo, filtros, cambios de estado (confirmar, completar, no-show, cancelar)
- **Profesionales**: CRUD con asignación de servicios y colores personalizados
- **Servicios**: CRUD con duración, precio, depósito
- **Horarios**: configuración por profesional o por negocio (días, rangos, breaks)
- **Bloqueos**: vacaciones, feriados, ausencias puntuales
- **Clientes**: lista, búsqueda, historial, notas, no-show tracking
- **Analytics**: turnos por día, servicios top, horarios pico, fuentes
- **Bot config**: encender/apagar bot, personalidad, mensaje extra
- **Ajustes**: reglas de turnos, anticipación mínima/máxima, recordatorios

### Página pública de reserva
- Mobile-first, 4 pasos (servicio → profesional → horario → datos)
- Slot picker en tiempo real consultando SlotCalculator
- Confirmación con número de turno y mensaje de WhatsApp automático
- URL: `/book/{slug-del-negocio}`

### Bot WhatsApp con Claude
Entrada: `POST /api/webhook/whatsapp` (configurar en Twilio Console)

El bot usa **Claude Haiku 4.5** con function calling. Tools disponibles:
- `list_services` — lista servicios
- `list_professionals` — lista profesionales por servicio
- `find_available_slots` — consulta SlotCalculator
- `create_booking` — crea el turno
- `get_client_bookings` — próximos turnos del cliente
- `cancel_booking` — cancela un turno

Conversación persistente en tabla `bot_conversations` (historial + estado JSON).

### Recordatorios automáticos
Endpoint: `GET /api/reminders/cron?secret={CRON_SECRET}` → envía recordatorios por WhatsApp a turnos de las próximas N horas (configurable por negocio).

Alternativa CLI:
```bash
php scripts/reminders_cron.php
```

Configurar en cron cada hora:
```cron
0 * * * * cd /path/to/turneroya && php scripts/reminders_cron.php >> storage/logs/reminders.log 2>&1
```

## Arquitectura — El corazón: SlotCalculator

`src/Services/SlotCalculator.php` es la lógica más crítica. Para una fecha + servicio + profesional:

1. Obtiene el schedule del día (fallback del negocio si no hay del profesional).
2. Construye rangos de trabajo considerando breaks.
3. Sustrae blockouts (vacaciones/bloqueos).
4. Sustrae bookings existentes (interval subtraction).
5. Genera slots alineados al `slot_duration` del negocio.
6. Descarta slots que no permiten completar el servicio.
7. Aplica `min_advance_hours`.
8. Devuelve slots con `{start, end, label, iso}`.

**Anti-doble-booking:** índice `UNIQUE` en `bookings(professional_id, date, start_time)` con WHERE sobre estados no cancelados + revalidación en `BookingService::createBooking()` antes del INSERT. Dos usuarios reservando el mismo slot — solo uno queda.

## Deploy

### Hosting compartido (cPanel/Plesk)
1. Subir todo a `public_html/turneroya/` (salvo `public/` que va a `public_html/`)
2. Cambiar en `public/index.php` el `BASE_PATH` si corresponde
3. Crear DB PostgreSQL desde el panel
4. `composer install --no-dev` en la terminal SSH
5. Configurar `.env`
6. Ejecutar `php scripts/migrate.php`

### VPS (Ubuntu + Nginx)
```nginx
server {
    listen 80;
    server_name turneroya.tu-dominio.com;
    root /var/www/turneroya/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Cron del sistema:
```bash
crontab -e
# Agregar:
0 * * * * cd /var/www/turneroya && php scripts/reminders_cron.php >> storage/logs/reminders.log 2>&1
```

### Twilio WhatsApp
1. Cuenta en [Twilio Console](https://console.twilio.com)
2. WhatsApp senders → agregar número
3. Configurar webhook `POST`:
   - **URL:** `https://tu-dominio.com/api/webhook/whatsapp`
   - **Method:** `POST`

### MercadoPago (opcional)
1. Obtener Access Token en [MercadoPago Developers](https://www.mercadopago.com.ar/developers)
2. Configurar en `.env`
3. Webhook de notificaciones: `https://tu-dominio.com/api/webhook/mercadopago`

## Testing manual del flujo completo

1. `composer serve`
2. Entrar a http://localhost:8000/register → crear cuenta
3. Completar onboarding
4. Ir a Servicios → crear 2-3 servicios
5. Ir a Profesionales → crear 1-2
6. Ir a Horarios → configurar días de atención
7. Copiar el link público del dashboard
8. Abrir en incógnito → reservar un turno
9. Volver al dashboard → ver el turno en el calendario
10. Probar cambiar estado, cancelar, etc.

## Licencia

Proprietary. Todos los derechos reservados.
