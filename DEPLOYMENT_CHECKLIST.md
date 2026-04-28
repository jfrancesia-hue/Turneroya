# Deployment Checklist · TurneroYa

> Lista de pasos manuales para activar el Sprint 1+2 en producción.
> Tiempo total estimado: **~10 minutos** (sin contar la aprobación Meta de Twilio templates, que es opcional).
>
> Marcá cada paso con `[x]` cuando lo termines.

---

## Antes de empezar

- [ ] El último commit en `origin/main` es `dde3618` (o más nuevo) — verificá con `git log --oneline -1`
- [ ] Los tests locales pasan: `composer test` → `OK (89 tests, ...)`
- [ ] Tenés acceso a:
  - [ ] Render Dashboard del servicio TurneroYa
  - [ ] MercadoPago Developers (https://www.mercadopago.com.ar/developers)
  - [ ] Twilio Console (opcional, para botones)

---

## 1 · Render · variables de entorno

Render Dashboard → Service `turneroya` → **Environment** → agregar las siguientes:

```
TWILIO_VALIDATE_SIGNATURE=true
MERCADOPAGO_WEBHOOK_SECRET=<lo generamos en el paso 3>
PAYMENT_EXPIRATION_MINUTES=15
```

> ⚠️ Sin `MERCADOPAGO_WEBHOOK_SECRET`, las firmas no se verifican (modo "permisivo" para rollout gradual). En cuanto tengas el secret del paso 3, pegalo acá.

- [ ] `TWILIO_VALIDATE_SIGNATURE=true` configurada
- [ ] `PAYMENT_EXPIRATION_MINUTES=15` configurada (opcional, default OK)

---

## 2 · Render · correr migraciones

Después de que Render redeploye con el nuevo código:

```bash
# Vía Render Shell o SSH
composer migrate
```

Esto aplica:
- `003_security_idempotency.sql` (tablas `webhook_events`, `rate_limits`)
- `004_waitlist.sql` (tabla `waitlist_entries`)
- `005_booking_payment_expiration.sql` (status `PENDING_PAYMENT` + columnas)

Las migraciones son **idempotentes** (`IF NOT EXISTS`). Si las corrés dos veces, no pasa nada.

- [ ] Migraciones aplicadas sin errores
- [ ] Verificación: `psql ... -c "\dt webhook_events rate_limits waitlist_entries"` muestra las 3 tablas

---

## 3 · MercadoPago · webhook + secret

### 3a. Configurar webhook

MercadoPago Developers → Tu Aplicación → **Webhooks** → **Configurar notificaciones**

- **URL de producción:** `https://tu-dominio-render.com/api/webhook/mercadopago`
- **Eventos a suscribir** (3):
  - [x] `Pagos` (payment)
  - [x] `Suscripción de aplicación` (subscription_preapproval)
  - [x] `Pagos autorizados de suscripción` (subscription_authorized_payment)
- [ ] Webhook configurado y guardado

### 3b. Generar y copiar el secret

En la misma pantalla de Webhooks:

1. Hacé click en **"Generar clave secreta"** (o ver clave existente)
2. Copiá el valor (formato típico: `xxxx-xxxx-xxxx`)
3. Volvé a **Render → Environment**
4. Pegá el valor en `MERCADOPAGO_WEBHOOK_SECRET=...`
5. **Save Changes** → Render redeploya automático

- [ ] Secret generado en MP Dashboard
- [ ] Secret pegado en Render env vars
- [ ] Render terminó de redeployar (verde en el panel)

---

## 4 · Render · cron jobs

Render Dashboard → Service `turneroya` → **Jobs** (o **Cron Jobs**) → **New Cron Job**

### 4a. Expirar pagos pendientes

```yaml
Name:     expire-pending-payments
Schedule: 0 * * * *
Command:  curl -fsS "https://$RENDER_EXTERNAL_HOSTNAME/api/payments/expire?secret=$CRON_SECRET"
```

- [ ] Job creado y activo

### 4b. Limpieza de datos viejos (opcional pero recomendado)

```yaml
Name:     cleanup-old-records
Schedule: 30 3 * * *
Command:  composer cleanup
```

- [ ] Job creado y activo

> Verificación: ambos jobs deberían ejecutarse en su próximo slot. Mirá los logs en Render → Job → Logs.

---

## 5 · Smoke test post-deploy

Después de todo lo anterior, validá que funciona:

### 5a. Webhook de Twilio (firma)

Mandar un mensaje de WhatsApp al número del bot. Mirar logs:
```bash
# En Render → Logs
[Webhook WhatsApp] OK | from=+5491xxx | reply=...
```

Si ves `[WebhookController] firma inválida desde IP ...`, revisar `TWILIO_VALIDATE_SIGNATURE` y que el `TWILIO_AUTH_TOKEN` sea correcto.

- [ ] Bot responde a un mensaje real

### 5b. Webhook de MercadoPago (firma)

Hacer un pago de prueba (modo sandbox) o usar el botón **"Test webhook"** de MP Dashboard. Mirar logs:
```bash
[MercadoPago webhook] received | topic=payment | id=...
```

Si ves `403 invalid signature`, revisar `MERCADOPAGO_WEBHOOK_SECRET`.

- [ ] Webhook MP procesa eventos de prueba

### 5c. Cron de expiración

Forzar manualmente:
```bash
curl "https://tu-dominio.com/api/payments/expire?secret=$CRON_SECRET"
# Esperado: {"ok":true,"expired":0,"businesses_processed":0,...}
```

- [ ] Endpoint responde 200 con JSON correcto

---

## 6 · Twilio Content Templates · OPCIONAL

Sin esto, los recordatorios y confirmaciones siguen llegando como **texto plano** (funcional). Activá esto si querés botones interactivos (Confirmar / Cancelar / Reagendar).

Twilio Console → **Content Builder** → **Create Content**:

### Template "reminder"

- **Type:** Quick Reply
- **Body:**
  ```
  ¡Hola {{1}}! Te recordamos tu turno en {{2}} el {{3}} a las {{4}} ({{5}}). ¿Confirmás?
  ```
- **Botones (3):**
  - "✅ Confirmar" → Payload: `{{6}}`
  - "❌ Cancelar" → Payload: `{{7}}`
  - "🔄 Reagendar" → Payload: `{{8}}`
- **Submit for WhatsApp Approval** → esperar 24-72hs

### Template "confirmation"

Similar, sin botón "Confirmar":
- "❌ Cancelar" → `{{7}}`
- "🔄 Reagendar" → `{{8}}`

### Una vez aprobados

Copiar los HX SIDs (`HXxxxxxx...`) a Render env:

```
TWILIO_REMINDER_CONTENT_SID=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_CONFIRMATION_CONTENT_SID=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

- [ ] Templates creados en Twilio
- [ ] Templates aprobados por Meta
- [ ] HX SIDs configurados en Render

---

## Resumen final

Una vez completado todo lo bloqueante (1, 2, 3, 4, 5):

- [x] **Sprint 1: webhooks blindados** ← funciona desde paso 3
- [x] **Sprint 2: reschedule via WhatsApp** ← funciona desde el deploy
- [x] **Sprint 2: lista de espera** ← funciona desde paso 2 (necesita la tabla)
- [x] **Sprint 2: seña obligatoria con expiración** ← funciona desde paso 4 (necesita el cron)
- [ ] **Sprint 2: botones interactivos** ← funciona cuando Meta apruebe templates (paso 6)

---

## Soporte

Si algo falla:
- Logs de Render: muestran errores con prefijo `[Webhook ...]`, `[BookingService ...]`, `[NotificationService ...]`
- Tests locales: `composer test` debería seguir dando 89/89 OK
- Reporte visual del estado: abrir `docs/sprint-report.html`
