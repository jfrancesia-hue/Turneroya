-- ============================================================
-- TurneroYa - Migration 004: Lista de espera (waitlist)
-- ============================================================
-- Cuando un cliente quiere un horario que está ocupado, entra a
-- la waitlist. Si alguien cancela y libera un slot que matchee,
-- le avisamos automáticamente por WhatsApp.
-- ============================================================

CREATE TABLE IF NOT EXISTS waitlist_entries (
    id                   TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    business_id          TEXT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    client_id            TEXT NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    service_id           TEXT NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    professional_id      TEXT REFERENCES professionals(id) ON DELETE CASCADE,
    -- Rango de fechas en el que el cliente acepta (NULL en end = sin tope)
    preferred_date_from  DATE NOT NULL,
    preferred_date_to    DATE,
    -- Rango horario opcional ("solo me sirve a la mañana")
    preferred_time_from  TEXT,
    preferred_time_to    TEXT,
    notes                TEXT,
    status               TEXT NOT NULL DEFAULT 'PENDING'
                         CHECK (status IN ('PENDING','NOTIFIED','CONVERTED','EXPIRED','CANCELLED')),
    notified_at          TIMESTAMPTZ,
    notified_booking_id  TEXT REFERENCES bookings(id) ON DELETE SET NULL,
    converted_at         TIMESTAMPTZ,
    expires_at           TIMESTAMPTZ NOT NULL DEFAULT (NOW() + INTERVAL '30 days'),
    source               TEXT NOT NULL DEFAULT 'WEB' CHECK (source IN ('WEB','WHATSAPP_BOT','MANUAL')),
    created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_waitlist_business_status ON waitlist_entries(business_id, status);
CREATE INDEX IF NOT EXISTS idx_waitlist_match ON waitlist_entries(business_id, service_id, professional_id, status, created_at)
    WHERE status = 'PENDING';
CREATE INDEX IF NOT EXISTS idx_waitlist_expires ON waitlist_entries(expires_at) WHERE status = 'PENDING';

DO $$
BEGIN
    DROP TRIGGER IF EXISTS trg_waitlist_entries_updated_at ON waitlist_entries;
    CREATE TRIGGER trg_waitlist_entries_updated_at BEFORE UPDATE ON waitlist_entries
        FOR EACH ROW EXECUTE FUNCTION set_updated_at();
END $$;
