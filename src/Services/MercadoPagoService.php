<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use TurneroYa\Models\Booking;
use TurneroYa\Models\Service as ServiceModel;
use TurneroYa\Models\Business;

/**
 * Servicio para cobrar depósitos/señas de reservas vía MercadoPago.
 * Crea preferencias de pago y maneja el webhook de notificaciones.
 */
final class MercadoPagoService
{
    private bool $configured;

    public function __construct()
    {
        $token = (string) config('services.mercadopago.access_token');
        $this->configured = $token !== '';
        if ($this->configured) {
            MercadoPagoConfig::setAccessToken($token);
        }
    }

    private function ensureConfigured(): void
    {
        if (!$this->configured) {
            throw new \RuntimeException(
                'MercadoPago no está configurado. Definí MERCADOPAGO_ACCESS_TOKEN en el entorno.'
            );
        }
    }

    /**
     * Crea una preferencia de pago para el depósito de una reserva.
     * @return string init_point (URL para redirigir al cliente)
     */
    public function createDepositPreference(string $bookingId): string
    {
        $this->ensureConfigured();
        $booking = Booking::findWithRelations($bookingId);
        if (!$booking) throw new \RuntimeException('Booking no encontrado');
        $service = ServiceModel::find($booking['service_id']);
        if (!$service) throw new \RuntimeException('Servicio no encontrado');
        $business = Business::find($booking['business_id']);
        if (!$business) throw new \RuntimeException('Negocio no encontrado');

        if (empty($service['requires_deposit']) || empty($service['deposit_amount'])) {
            throw new \RuntimeException('Este servicio no requiere depósito');
        }

        $client = new PreferenceClient();
        $preference = $client->create([
            'items' => [[
                'title' => 'Seña - ' . $service['name'],
                'description' => 'Reserva en ' . $business['name'] . ' - Turno #' . $booking['booking_number'],
                'quantity' => 1,
                'unit_price' => (float) $service['deposit_amount'],
                'currency_id' => 'ARS',
            ]],
            'external_reference' => $bookingId,
            'back_urls' => [
                'success' => url('/book/' . $business['slug'] . '/success/' . $bookingId),
                'failure' => url('/book/' . $business['slug']),
                'pending' => url('/book/' . $business['slug'] . '/success/' . $bookingId),
            ],
            'auto_return' => 'approved',
            'notification_url' => url('/api/webhook/mercadopago'),
            'payer' => [
                'name' => $booking['client_name'],
                'email' => $booking['client_email'] ?? 'cliente@reservia.app',
            ],
        ]);

        return $preference->init_point ?? '';
    }

    /**
     * Marca el depósito como pagado a partir del external_reference.
     */
    public function markDepositPaid(string $bookingId, ?string $mpPaymentId = null): void
    {
        $booking = Booking::find($bookingId);
        if (!$booking) return;

        // Si el booking ya fue cancelado (cron de expiración o cancel manual),
        // NO revivirlo: el slot puede haber sido tomado por otro o por waitlist.
        if ($booking['status'] === 'CANCELLED') {
            error_log(sprintf(
                '[MercadoPagoService] Pago tardío detectado en booking %s (status=CANCELLED). Pago MP %s. TODO: refund automático.',
                $bookingId,
                $mpPaymentId ?? 'unknown'
            ));
            // Guardamos el deposit_mp_id por si manualmente refundean
            Booking::update($bookingId, ['deposit_mp_id' => $mpPaymentId]);
            return;
        }

        // Solo confirmar si está esperando pago (idempotencia: evita reprocesar
        // webhooks duplicados que ya fueron marcados CONFIRMED).
        if ($booking['status'] !== 'PENDING_PAYMENT') {
            // Ya CONFIRMED u otro estado: actualizar mp_id pero no cambiar status
            Booking::update($bookingId, [
                'deposit_paid' => true,
                'deposit_mp_id' => $mpPaymentId,
            ]);
            return;
        }

        Booking::update($bookingId, [
            'deposit_paid' => true,
            'deposit_mp_id' => $mpPaymentId,
            'status' => 'CONFIRMED',
        ]);
    }
}
