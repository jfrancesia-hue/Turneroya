-- ============================================================
-- TurneroYa - Migration 003: Seguridad e idempotencia
-- ============================================================
-- Tablas de soporte para:
--  1. Idempotencia de webhooks (Twilio, MercadoPago)
--  2. Rate limiting por bucket+ip+ventana
-- ============================================================

-- Tabla de idempotencia para webhooks
CREATE TABLE IF NOT EXISTS webhook_events (
    id            TEXT PRIMARY KEY DEFAULT gen_random_uuid()::text,
    provider      TEXT NOT NULL CHECK (provider IN ('twilio','mercadopago')),
    external_id   TEXT NOT NULL,
    payload       JSONB,
    received_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    processed_at  TIMESTAMPTZ,
    UNIQUE(provider, external_id)
);
CREATE INDEX IF NOT EXISTS idx_webhook_events_received ON webhook_events(received_at DESC);

-- Tabla de rate limit por bucket+ip+ventana
CREATE TABLE IF NOT EXISTS rate_limits (
    id           BIGSERIAL PRIMARY KEY,
    bucket       TEXT NOT NULL,
    ip           TEXT NOT NULL,
    window_start TIMESTAMPTZ NOT NULL,
    count        INT NOT NULL DEFAULT 1,
    UNIQUE(bucket, ip, window_start)
);
CREATE INDEX IF NOT EXISTS idx_rate_limits_window ON rate_limits(window_start);
