-- ============================================================
-- TurneroYa - Migration 002: Planes, Suscripciones y Facturación
-- ============================================================
-- Objetivo: habilitar el SaaS comercial con planes, trial de 14 días,
-- suscripciones recurrentes vía MercadoPago Preapproval, facturas y
-- contadores de uso mensuales para enforcement de límites.
-- ============================================================

-- ------------------------------------------------------------
-- PLANS: catálogo de planes (Starter / Negocio / Multi-Sucursal)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plans (
    id                      TEXT PRIMARY KEY,
    name                    TEXT NOT NULL,
    tagline                 TEXT,
    price_monthly           NUMERIC(10,2) NOT NULL DEFAULT 0,
    price_yearly            NUMERIC(10,2),
    currency                TEXT NOT NULL DEFAULT 'ARS',

    -- Límites (NULL = ilimitado)
    max_bookings_per_month  INT,
    max_professionals       INT,
    max_services            INT,
    max_businesses          INT NOT NULL DEFAULT 1,

    -- Features (boolean switches)
    has_whatsapp_bot        BOOLEAN NOT NULL DEFAULT FALSE,
    has_advanced_analytics  BOOLEAN NOT NULL DEFAULT FALSE,
    has_public_booking      BOOLEAN NOT NULL DEFAULT TRUE,
    has_reminders           BOOLEAN NOT NULL DEFAULT TRUE,
    has_deposits            BOOLEAN NOT NULL DEFAULT FALSE,
    has_custom_branding     BOOLEAN NOT NULL DEFAULT FALSE,
    has_api_access          BOOLEAN NOT NULL DEFAULT FALSE,
    has_multi_location      BOOLEAN NOT NULL DEFAULT FALSE,
    has_priority_support    BOOLEAN NOT NULL DEFAULT FALSE,

    -- Marketing
    is_featured             BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order              INT NOT NULL DEFAULT 0,
    is_active               BOOLEAN NOT NULL DEFAULT TRUE,
    features_json           JSONB NOT NULL DEFAULT '[]'::jsonb,

    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_plans_active ON plans(is_active, sort_order);

-- ------------------------------------------------------------
-- SUBSCRIPTIONS: suscripción activa del negocio
-- Un business tiene como máximo 1 subscription activa.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS subscriptions (
    id                      TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    business_id             TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    plan_id                 TEXT NOT NULL REFERENCES plans(id),

    status                  TEXT NOT NULL DEFAULT 'TRIALING'
                            CHECK (status IN ('TRIALING','ACTIVE','PAST_DUE','CANCELLED','EXPIRED','PAUSED')),

    billing_cycle           TEXT NOT NULL DEFAULT 'MONTHLY'
                            CHECK (billing_cycle IN ('MONTHLY','YEARLY')),

    -- Fechas clave del ciclo
    trial_ends_at           TIMESTAMPTZ,
    current_period_start    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    current_period_end      TIMESTAMPTZ NOT NULL,
    cancel_at_period_end    BOOLEAN NOT NULL DEFAULT FALSE,
    cancelled_at            TIMESTAMPTZ,

    -- Integración MercadoPago Preapproval (suscripciones recurrentes)
    mp_preapproval_id       TEXT UNIQUE,
    mp_payer_id             TEXT,
    mp_payer_email          TEXT,
    mp_last_payment_id      TEXT,
    mp_next_payment_date    TIMESTAMPTZ,
    mp_init_point           TEXT,

    -- Precio al momento de la suscripción (para inmutabilidad)
    amount                  NUMERIC(10,2) NOT NULL,
    currency                TEXT NOT NULL DEFAULT 'ARS',

    metadata                JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
-- Un negocio solo puede tener UNA suscripción activa/trialing a la vez
CREATE UNIQUE INDEX IF NOT EXISTS uniq_subscription_active_per_business
    ON subscriptions(business_id)
    WHERE status IN ('TRIALING','ACTIVE','PAST_DUE','PAUSED');
CREATE INDEX IF NOT EXISTS idx_subscriptions_business ON subscriptions(business_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_period_end ON subscriptions(current_period_end);

-- ------------------------------------------------------------
-- SUBSCRIPTION_INVOICES: histórico de pagos/facturas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS subscription_invoices (
    id                      TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    subscription_id         TEXT NOT NULL REFERENCES subscriptions(id) ON DELETE CASCADE,
    business_id             TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,

    amount                  NUMERIC(10,2) NOT NULL,
    currency                TEXT NOT NULL DEFAULT 'ARS',
    status                  TEXT NOT NULL DEFAULT 'PENDING'
                            CHECK (status IN ('PENDING','PAID','FAILED','REFUNDED','CANCELLED')),

    period_start            TIMESTAMPTZ NOT NULL,
    period_end              TIMESTAMPTZ NOT NULL,
    due_date                TIMESTAMPTZ,
    paid_at                 TIMESTAMPTZ,

    -- MercadoPago
    mp_payment_id           TEXT UNIQUE,
    mp_payment_status       TEXT,
    mp_payment_method       TEXT,

    -- AFIP (cuando se integre con FacturAI)
    afip_cae                TEXT,
    afip_voucher_number     TEXT,
    afip_invoice_url        TEXT,

    metadata                JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_invoices_subscription ON subscription_invoices(subscription_id);
CREATE INDEX IF NOT EXISTS idx_invoices_business ON subscription_invoices(business_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON subscription_invoices(status);

-- ------------------------------------------------------------
-- USAGE_COUNTERS: contadores de uso por ciclo mensual
-- Se reinician al rotar el período de la suscripción.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usage_counters (
    id                      TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    business_id             TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    period_start            DATE NOT NULL,
    period_end              DATE NOT NULL,

    bookings_count          INT NOT NULL DEFAULT 0,
    bot_messages_count      INT NOT NULL DEFAULT 0,
    reminders_sent_count    INT NOT NULL DEFAULT 0,

    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(business_id, period_start)
);
CREATE INDEX IF NOT EXISTS idx_usage_business_period ON usage_counters(business_id, period_start DESC);

-- ------------------------------------------------------------
-- Trigger: updated_at en nuevas tablas
-- ------------------------------------------------------------
DO $$
DECLARE t TEXT;
BEGIN
    FOR t IN SELECT unnest(ARRAY['plans','subscriptions','usage_counters']) LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS trg_%I_updated_at ON %I', t, t);
        EXECUTE format('CREATE TRIGGER trg_%I_updated_at BEFORE UPDATE ON %I FOR EACH ROW EXECUTE FUNCTION set_updated_at()', t, t);
    END LOOP;
END $$;

-- ------------------------------------------------------------
-- Ajustes a businesses: agregar subscription_id y billing_email
-- El campo "plan" existente queda como denormalización/caché.
-- ------------------------------------------------------------
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS current_subscription_id TEXT REFERENCES subscriptions(id) ON DELETE SET NULL;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS billing_email TEXT;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS billing_cuit TEXT;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS billing_name TEXT;

-- Permitir el nuevo valor "FREE" y "TRIAL" en el campo plan existente.
ALTER TABLE businesses DROP CONSTRAINT IF EXISTS businesses_plan_check;
ALTER TABLE businesses ADD CONSTRAINT businesses_plan_check
    CHECK (plan IN ('FREE','TRIAL','STARTER','NEGOCIO','MULTI_SUCURSAL'));
ALTER TABLE businesses ALTER COLUMN plan SET DEFAULT 'TRIAL';

-- ------------------------------------------------------------
-- SEED: catálogo de planes inicial (pricing en ARS, abril 2026)
-- ------------------------------------------------------------
INSERT INTO plans (
    id, name, tagline, price_monthly, price_yearly,
    max_bookings_per_month, max_professionals, max_services, max_businesses,
    has_whatsapp_bot, has_advanced_analytics, has_public_booking, has_reminders,
    has_deposits, has_custom_branding, has_api_access, has_multi_location, has_priority_support,
    is_featured, sort_order, features_json
) VALUES
(
    'FREE',
    'Free',
    'Probá la plataforma sin costo',
    0, 0,
    50, 1, 3, 1,
    FALSE, FALSE, TRUE, TRUE,
    FALSE, FALSE, FALSE, FALSE, FALSE,
    FALSE, 0,
    '["Hasta 50 turnos/mes","1 profesional","3 servicios","Página pública de reserva","Recordatorios por email","Soporte por email"]'::jsonb
),
(
    'STARTER',
    'Starter',
    'Para profesionales independientes',
    9900, 99000,
    500, 2, 10, 1,
    FALSE, FALSE, TRUE, TRUE,
    TRUE, FALSE, FALSE, FALSE, FALSE,
    FALSE, 1,
    '["Hasta 500 turnos/mes","2 profesionales","10 servicios","Señas con MercadoPago","Recordatorios WhatsApp","Calendario completo","Soporte por email"]'::jsonb
),
(
    'NEGOCIO',
    'Negocio',
    'El más elegido - con bot IA de WhatsApp',
    24900, 249000,
    NULL, 5, NULL, 1,
    TRUE, TRUE, TRUE, TRUE,
    TRUE, TRUE, FALSE, FALSE, TRUE,
    TRUE, 2,
    '["Turnos ilimitados","Bot WhatsApp con IA (Claude)","Recordatorios automáticos","Hasta 5 profesionales","Pagos con MercadoPago","Analytics completo","Soporte prioritario"]'::jsonb
),
(
    'MULTI_SUCURSAL',
    'Multi-Sucursal',
    'Para cadenas y franquicias',
    59900, 599000,
    NULL, NULL, NULL, 10,
    TRUE, TRUE, TRUE, TRUE,
    TRUE, TRUE, TRUE, TRUE, TRUE,
    FALSE, 3,
    '["Todo lo de Negocio","Sucursales ilimitadas","Profesionales ilimitados","API REST","Integraciones custom","Manager de cuenta dedicado","SLA garantizado"]'::jsonb
)
ON CONFLICT (id) DO UPDATE SET
    name = EXCLUDED.name,
    tagline = EXCLUDED.tagline,
    price_monthly = EXCLUDED.price_monthly,
    price_yearly = EXCLUDED.price_yearly,
    max_bookings_per_month = EXCLUDED.max_bookings_per_month,
    max_professionals = EXCLUDED.max_professionals,
    max_services = EXCLUDED.max_services,
    max_businesses = EXCLUDED.max_businesses,
    has_whatsapp_bot = EXCLUDED.has_whatsapp_bot,
    has_advanced_analytics = EXCLUDED.has_advanced_analytics,
    has_public_booking = EXCLUDED.has_public_booking,
    has_reminders = EXCLUDED.has_reminders,
    has_deposits = EXCLUDED.has_deposits,
    has_custom_branding = EXCLUDED.has_custom_branding,
    has_api_access = EXCLUDED.has_api_access,
    has_multi_location = EXCLUDED.has_multi_location,
    has_priority_support = EXCLUDED.has_priority_support,
    is_featured = EXCLUDED.is_featured,
    sort_order = EXCLUDED.sort_order,
    features_json = EXCLUDED.features_json,
    updated_at = NOW();
