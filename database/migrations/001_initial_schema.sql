-- ============================================================
-- TurneroYa - Schema inicial PostgreSQL
-- ============================================================

-- Extensiones
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ------------------------------------------------------------
-- BUSINESSES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS businesses (
    id                      TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    name                    TEXT NOT NULL,
    slug                    TEXT UNIQUE NOT NULL,
    type                    TEXT NOT NULL CHECK (type IN ('SALON','CLINIC','WORKSHOP','STUDIO','GYM','VET','DENTIST','LAWYER','ACCOUNTANT','OTHER')),
    description             TEXT,
    logo                    TEXT,
    phone                   TEXT,
    whatsapp                TEXT,
    email                   TEXT,
    address                 TEXT,
    city                    TEXT,
    province                TEXT,
    country                 TEXT NOT NULL DEFAULT 'AR',
    timezone                TEXT NOT NULL DEFAULT 'America/Argentina/Buenos_Aires',
    currency                TEXT NOT NULL DEFAULT 'ARS',

    -- Config de turnos
    slot_duration           INT NOT NULL DEFAULT 30,
    max_advance_days        INT NOT NULL DEFAULT 30,
    min_advance_hours       INT NOT NULL DEFAULT 2,
    allow_cancellation      BOOLEAN NOT NULL DEFAULT TRUE,
    cancellation_hours_limit INT NOT NULL DEFAULT 4,
    require_confirmation    BOOLEAN NOT NULL DEFAULT FALSE,
    auto_reminder           BOOLEAN NOT NULL DEFAULT TRUE,
    reminder_hours_before   INT NOT NULL DEFAULT 24,

    -- Bot
    bot_enabled             BOOLEAN NOT NULL DEFAULT TRUE,
    bot_welcome_message     TEXT,
    bot_personality         TEXT NOT NULL DEFAULT 'profesional y amigable',

    -- Plan
    plan                    TEXT NOT NULL DEFAULT 'STARTER' CHECK (plan IN ('STARTER','NEGOCIO','MULTI_SUCURSAL')),
    plan_expires_at         TIMESTAMPTZ,
    mercadopago_customer_id TEXT,

    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_businesses_slug ON businesses(slug);

-- ------------------------------------------------------------
-- USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    email           TEXT UNIQUE NOT NULL,
    password_hash   TEXT NOT NULL,
    name            TEXT,
    role            TEXT NOT NULL DEFAULT 'OWNER' CHECK (role IN ('OWNER','ADMIN','STAFF')),
    business_id     TEXT REFERENCES businesses(id) ON DELETE SET NULL,
    email_verified_at TIMESTAMPTZ,
    last_login_at   TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_business ON users(business_id);

-- ------------------------------------------------------------
-- PROFESSIONALS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS professionals (
    id              TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    name            TEXT NOT NULL,
    email           TEXT,
    phone           TEXT,
    avatar_url      TEXT,
    specialization  TEXT,
    bio             TEXT,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order      INT NOT NULL DEFAULT 0,
    color           TEXT NOT NULL DEFAULT '#3B82F6',
    business_id     TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_professionals_business ON professionals(business_id);

-- ------------------------------------------------------------
-- SERVICES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id               TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    name             TEXT NOT NULL,
    description      TEXT,
    duration         INT NOT NULL,
    price            NUMERIC(10,2),
    currency         TEXT NOT NULL DEFAULT 'ARS',
    color            TEXT NOT NULL DEFAULT '#10B981',
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order       INT NOT NULL DEFAULT 0,
    requires_deposit BOOLEAN NOT NULL DEFAULT FALSE,
    deposit_amount   NUMERIC(10,2),
    business_id      TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_services_business ON services(business_id);

-- ------------------------------------------------------------
-- PROFESSIONAL <-> SERVICE (muchos-a-muchos con overrides)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS professional_services (
    id              TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    professional_id TEXT NOT NULL REFERENCES professionals(id) ON DELETE CASCADE,
    service_id      TEXT NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    custom_duration INT,
    custom_price    NUMERIC(10,2),
    UNIQUE(professional_id, service_id)
);

-- ------------------------------------------------------------
-- SCHEDULES (horarios de atención)
-- day_of_week: 0=domingo, 1=lunes, ..., 6=sábado
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS schedules (
    id              TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    day_of_week     INT NOT NULL CHECK (day_of_week BETWEEN 0 AND 6),
    start_time      TEXT NOT NULL,
    end_time        TEXT NOT NULL,
    break_start     TEXT,
    break_end       TEXT,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    professional_id TEXT REFERENCES professionals(id) ON DELETE CASCADE,
    business_id     TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_schedules_business ON schedules(business_id);
CREATE INDEX IF NOT EXISTS idx_schedules_professional ON schedules(professional_id);

-- ------------------------------------------------------------
-- BLOCKOUTS (feriados, vacaciones, bloqueos temporales)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blockouts (
    id              TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    title           TEXT,
    start_date      TIMESTAMPTZ NOT NULL,
    end_date        TIMESTAMPTZ NOT NULL,
    all_day         BOOLEAN NOT NULL DEFAULT FALSE,
    professional_id TEXT REFERENCES professionals(id) ON DELETE CASCADE,
    business_id     TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_blockouts_business ON blockouts(business_id);
CREATE INDEX IF NOT EXISTS idx_blockouts_dates ON blockouts(start_date, end_date);

-- ------------------------------------------------------------
-- CLIENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clients (
    id                TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    name              TEXT NOT NULL,
    phone             TEXT,
    email             TEXT,
    whatsapp_number   TEXT,
    notes             TEXT,
    tags              TEXT[] NOT NULL DEFAULT '{}',
    no_show_count     INT NOT NULL DEFAULT 0,
    total_bookings    INT NOT NULL DEFAULT 0,
    last_visit        TIMESTAMPTZ,
    business_id       TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(business_id, phone),
    UNIQUE(business_id, whatsapp_number)
);
CREATE INDEX IF NOT EXISTS idx_clients_business ON clients(business_id);

-- ------------------------------------------------------------
-- BOOKINGS
-- ------------------------------------------------------------
CREATE SEQUENCE IF NOT EXISTS booking_number_seq;

CREATE TABLE IF NOT EXISTS bookings (
    id                  TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    booking_number      INT NOT NULL DEFAULT nextval('booking_number_seq'),
    date                DATE NOT NULL,
    start_time          TEXT NOT NULL,
    end_time            TEXT NOT NULL,
    status              TEXT NOT NULL DEFAULT 'PENDING'
                        CHECK (status IN ('PENDING','CONFIRMED','COMPLETED','CANCELLED','NO_SHOW','RESCHEDULED')),
    source              TEXT NOT NULL DEFAULT 'WEB'
                        CHECK (source IN ('WEB','WHATSAPP_BOT','MANUAL','INSTAGRAM','GOOGLE')),
    notes               TEXT,
    internal_notes      TEXT,
    price               NUMERIC(10,2),
    deposit_paid        BOOLEAN NOT NULL DEFAULT FALSE,
    deposit_mp_id       TEXT,
    reminder_sent       BOOLEAN NOT NULL DEFAULT FALSE,
    confirmation_sent   BOOLEAN NOT NULL DEFAULT FALSE,
    client_id           TEXT NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    service_id          TEXT NOT NULL REFERENCES services(id),
    professional_id     TEXT REFERENCES professionals(id),
    business_id         TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_bookings_business_date ON bookings(business_id, date);
CREATE INDEX IF NOT EXISTS idx_bookings_professional_date ON bookings(professional_id, date);
CREATE INDEX IF NOT EXISTS idx_bookings_client ON bookings(client_id);
CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(status);
-- Índice único para evitar dobles reservas en el mismo profesional+fecha+hora (salvo canceladas)
CREATE UNIQUE INDEX IF NOT EXISTS uniq_booking_slot
    ON bookings(professional_id, date, start_time)
    WHERE status NOT IN ('CANCELLED','NO_SHOW','RESCHEDULED') AND professional_id IS NOT NULL;

-- ------------------------------------------------------------
-- BOT CONVERSATIONS (estado de conversaciones WhatsApp)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bot_conversations (
    id              TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    business_id     TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    whatsapp_number TEXT NOT NULL,
    state           JSONB NOT NULL DEFAULT '{}'::jsonb,
    messages        JSONB NOT NULL DEFAULT '[]'::jsonb,
    last_message_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(business_id, whatsapp_number)
);
CREATE INDEX IF NOT EXISTS idx_bot_conversations_phone ON bot_conversations(whatsapp_number);

-- ------------------------------------------------------------
-- BOOKING ANALYTICS (eventos para métricas)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS booking_analytics (
    id          TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    type        TEXT NOT NULL,
    metadata    JSONB,
    business_id TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_analytics_business ON booking_analytics(business_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_analytics_type ON booking_analytics(type);

-- ------------------------------------------------------------
-- Triggers: updated_at
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
$$ LANGUAGE plpgsql;

DO $$
DECLARE t TEXT;
BEGIN
    FOR t IN SELECT unnest(ARRAY['businesses','users','professionals','services','clients','bookings']) LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS trg_%I_updated_at ON %I', t, t);
        EXECUTE format('CREATE TRIGGER trg_%I_updated_at BEFORE UPDATE ON %I FOR EACH ROW EXECUTE FUNCTION set_updated_at()', t, t);
    END LOOP;
END $$;
