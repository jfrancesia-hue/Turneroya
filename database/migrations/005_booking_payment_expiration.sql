-- ============================================================
-- TurneroYa - Migration 005: Seña obligatoria con expiración
-- ============================================================
-- Cuando un servicio requiere seña, el booking se crea en estado
-- PENDING_PAYMENT con un payment_expires_at (default 15 min).
-- Si el cliente no paga antes de expirar, el cron lo cancela y
-- libera el slot (waitlist se entera por el flujo normal).
-- El índice anti-double-booking incluye PENDING_PAYMENT a propósito:
-- mientras el cliente está pagando, el slot queda "tomado".
-- ============================================================

ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_status_check;
ALTER TABLE bookings ADD CONSTRAINT bookings_status_check
    CHECK (status IN ('PENDING','CONFIRMED','COMPLETED','CANCELLED','NO_SHOW','RESCHEDULED','PENDING_PAYMENT'));

ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_expires_at TIMESTAMPTZ;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_init_point TEXT;

CREATE INDEX IF NOT EXISTS idx_bookings_payment_expiration
    ON bookings(payment_expires_at)
    WHERE status = 'PENDING_PAYMENT';

-- Recreamos el índice único anti-double-booking. PENDING_PAYMENT NO se excluye
-- a propósito: mientras una persona está pagando, el slot queda reservado.
DROP INDEX IF EXISTS uniq_booking_slot;
CREATE UNIQUE INDEX IF NOT EXISTS uniq_booking_slot
    ON bookings(professional_id, date, start_time)
    WHERE status NOT IN ('CANCELLED','NO_SHOW','RESCHEDULED') AND professional_id IS NOT NULL;
