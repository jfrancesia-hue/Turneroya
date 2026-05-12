<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use TurneroYa\Core\Database;
use TurneroYa\Models\Business;
use TurneroYa\Models\Service as ServiceModel;

final class GrowthEngine
{
    public function __construct(private readonly string $businessId)
    {
    }

    public function commandCenter(): array
    {
        return [
            'money' => $this->moneyMetrics(),
            'crm' => $this->crmSegments(),
            'agenda' => $this->agendaRecommendations(),
            'no_show' => $this->noShowIntelligence(),
            'automations' => $this->automationPlaybooks(),
            'locations' => $this->locationReadiness(),
        ];
    }

    public function clientInsight(string $clientId): array
    {
        $history = Database::fetchAll(
            "SELECT b.*, s.name AS service_name, s.price AS service_price
             FROM bookings b
             LEFT JOIN services s ON s.id = b.service_id
             WHERE b.business_id = :b AND b.client_id = :c
             ORDER BY b.date DESC, b.start_time DESC
             LIMIT 50",
            ['b' => $this->businessId, 'c' => $clientId]
        );

        $total = count($history);
        $noShows = count(array_filter($history, fn(array $b) => $b['status'] === 'NO_SHOW'));
        $cancelled = count(array_filter($history, fn(array $b) => $b['status'] === 'CANCELLED'));
        $completed = count(array_filter($history, fn(array $b) => in_array($b['status'], ['COMPLETED', 'CONFIRMED'], true)));
        $last = $history[0] ?? null;
        $daysSinceLast = null;
        if ($last && !empty($last['date'])) {
            $daysSinceLast = (new \DateTimeImmutable($last['date']))->diff(new \DateTimeImmutable('today'))->days;
        }

        $risk = 8;
        if ($total > 0) $risk += (int) round(($noShows / max(1, $total)) * 55);
        $risk += min(20, $cancelled * 4);
        if ($daysSinceLast !== null && $daysSinceLast > 90) $risk += 10;
        if ($completed >= 5 && $noShows === 0) $risk -= 10;
        $risk = max(0, min(99, $risk));

        $favorite = Database::fetchOne(
            "SELECT s.name, COUNT(*) AS total
             FROM bookings b
             INNER JOIN services s ON s.id = b.service_id
             WHERE b.business_id = :b AND b.client_id = :c
             GROUP BY s.name
             ORDER BY total DESC
             LIMIT 1",
            ['b' => $this->businessId, 'c' => $clientId]
        );

        return [
            'risk_score' => $risk,
            'risk_label' => $risk >= 65 ? 'Alto' : ($risk >= 35 ? 'Medio' : 'Bajo'),
            'completed' => $completed,
            'cancelled' => $cancelled,
            'no_shows' => $noShows,
            'days_since_last' => $daysSinceLast,
            'favorite_service' => $favorite['name'] ?? null,
            'next_action' => $this->clientNextAction($risk, $daysSinceLast, $favorite['name'] ?? null),
        ];
    }

    private function moneyMetrics(): array
    {
        $current = Database::fetchOne(
            "SELECT
                COALESCE(SUM(COALESCE(b.price, s.price, 0)) FILTER (WHERE b.status IN ('COMPLETED','CONFIRMED')), 0) AS revenue,
                COALESCE(SUM(COALESCE(b.price, s.price, 0)) FILTER (WHERE b.status IN ('CANCELLED','NO_SHOW')), 0) AS lost,
                COUNT(*) FILTER (WHERE b.status IN ('COMPLETED','CONFIRMED')) AS paid_like_bookings,
                COUNT(*) FILTER (WHERE b.status = 'NO_SHOW') AS no_shows
             FROM bookings b
             LEFT JOIN services s ON s.id = b.service_id
             WHERE b.business_id = :b AND b.date >= CURRENT_DATE - INTERVAL '30 days'",
            ['b' => $this->businessId]
        ) ?? [];

        $projected = Database::fetchOne(
            "SELECT
                COALESCE(SUM(COALESCE(b.price, s.price, 0)), 0) AS revenue,
                COUNT(*) AS bookings
             FROM bookings b
             LEFT JOIN services s ON s.id = b.service_id
             WHERE b.business_id = :b
               AND b.date >= CURRENT_DATE
               AND b.date < CURRENT_DATE + INTERVAL '30 days'
               AND b.status IN ('PENDING','CONFIRMED','PENDING_PAYMENT')",
            ['b' => $this->businessId]
        ) ?? [];

        $topServices = Database::fetchAll(
            "SELECT s.name,
                    COUNT(*) AS bookings,
                    COALESCE(SUM(COALESCE(b.price, s.price, 0)), 0) AS revenue
             FROM bookings b
             INNER JOIN services s ON s.id = b.service_id
             WHERE b.business_id = :b
               AND b.date >= CURRENT_DATE - INTERVAL '60 days'
               AND b.status IN ('COMPLETED','CONFIRMED')
             GROUP BY s.id, s.name
             ORDER BY revenue DESC, bookings DESC
             LIMIT 5",
            ['b' => $this->businessId]
        );

        return [
            'last_30_revenue' => (float) ($current['revenue'] ?? 0),
            'next_30_projected' => (float) ($projected['revenue'] ?? 0),
            'lost_to_absence' => (float) ($current['lost'] ?? 0),
            'paid_like_bookings' => (int) ($current['paid_like_bookings'] ?? 0),
            'projected_bookings' => (int) ($projected['bookings'] ?? 0),
            'no_shows' => (int) ($current['no_shows'] ?? 0),
            'top_services' => $topServices,
        ];
    }

    private function crmSegments(): array
    {
        $inactive = Database::fetchAll(
            "SELECT c.*, MAX(b.date) AS last_booking_date, COUNT(b.id) AS booking_count
             FROM clients c
             LEFT JOIN bookings b ON b.client_id = c.id AND b.business_id = c.business_id
             WHERE c.business_id = :b
             GROUP BY c.id
             HAVING MAX(b.date) IS NULL OR MAX(b.date) < CURRENT_DATE - INTERVAL '45 days'
             ORDER BY last_booking_date ASC NULLS FIRST, c.name ASC
             LIMIT 12",
            ['b' => $this->businessId]
        );

        $vip = Database::fetchAll(
            "SELECT c.*, COUNT(b.id) AS booking_count, MAX(b.date) AS last_booking_date
             FROM clients c
             INNER JOIN bookings b ON b.client_id = c.id
             WHERE c.business_id = :b AND b.status IN ('COMPLETED','CONFIRMED')
             GROUP BY c.id
             HAVING COUNT(b.id) >= 3
             ORDER BY booking_count DESC, last_booking_date DESC
             LIMIT 12",
            ['b' => $this->businessId]
        );

        $newLeads = Database::fetchAll(
            "SELECT c.*, COUNT(b.id) AS booking_count, MAX(b.date) AS last_booking_date
             FROM clients c
             LEFT JOIN bookings b ON b.client_id = c.id
             WHERE c.business_id = :b
             GROUP BY c.id
             HAVING COUNT(b.id) <= 1
             ORDER BY c.created_at DESC
             LIMIT 12",
            ['b' => $this->businessId]
        );

        return [
            'inactive' => $inactive,
            'vip' => $vip,
            'new_leads' => $newLeads,
            'campaigns' => $this->campaignPlaybooks(count($inactive), count($vip), count($newLeads)),
        ];
    }

    private function agendaRecommendations(): array
    {
        $services = ServiceModel::allByBusiness($this->businessId, true);
        $slots = [];
        $calculator = new SlotCalculator($this->businessId);

        foreach (array_slice($services, 0, 4) as $service) {
            try {
                $next = $calculator->nextAvailableSlots((string) $service['id'], null, 3);
                foreach ($next as $slot) {
                    $slots[] = [
                        'service_id' => $service['id'],
                        'service_name' => $service['name'],
                        'price' => $service['price'],
                        'date' => $slot['date'] ?? '',
                        'start_time' => $slot['start_time'] ?? '',
                        'professional_name' => $slot['professional_name'] ?? 'Equipo',
                    ];
                }
            } catch (\Throwable $e) {
                error_log('[GrowthEngine] slot recommendation failed: ' . $e->getMessage());
            }
        }

        $underusedPros = Database::fetchAll(
            "SELECT p.name, COUNT(b.id) AS bookings
             FROM professionals p
             LEFT JOIN bookings b ON b.professional_id = p.id
                AND b.date >= CURRENT_DATE - INTERVAL '14 days'
                AND b.status NOT IN ('CANCELLED','NO_SHOW')
             WHERE p.business_id = :b AND p.is_active = TRUE
             GROUP BY p.id, p.name
             ORDER BY bookings ASC, p.name ASC
             LIMIT 5",
            ['b' => $this->businessId]
        );

        return [
            'fillable_slots' => array_slice($slots, 0, 8),
            'underused_professionals' => $underusedPros,
            'recommendations' => [
                'Ofrecer los primeros huecos libres a clientes inactivos.',
                'Usar seña obligatoria para clientes con riesgo alto.',
                'Promocionar profesionales con menor ocupación en las próximas 48 horas.',
            ],
        ];
    }

    private function noShowIntelligence(): array
    {
        $riskyClients = Database::fetchAll(
            "SELECT c.*,
                    COUNT(b.id) AS booking_count,
                    COUNT(b.id) FILTER (WHERE b.status = 'NO_SHOW') AS no_show_bookings,
                    COUNT(b.id) FILTER (WHERE b.status = 'CANCELLED') AS cancelled_bookings
             FROM clients c
             LEFT JOIN bookings b ON b.client_id = c.id
             WHERE c.business_id = :b
             GROUP BY c.id
             HAVING COUNT(b.id) FILTER (WHERE b.status = 'NO_SHOW') > 0
                 OR c.no_show_count > 0
             ORDER BY no_show_bookings DESC, cancelled_bookings DESC, c.no_show_count DESC
             LIMIT 12",
            ['b' => $this->businessId]
        );

        $upcoming = Database::fetchAll(
            "SELECT b.*, c.name AS client_name, c.no_show_count, c.total_bookings,
                    s.name AS service_name, s.requires_deposit
             FROM bookings b
             INNER JOIN clients c ON c.id = b.client_id
             INNER JOIN services s ON s.id = b.service_id
             WHERE b.business_id = :b
               AND b.date >= CURRENT_DATE
               AND b.date < CURRENT_DATE + INTERVAL '14 days'
               AND b.status IN ('PENDING','CONFIRMED')
             ORDER BY c.no_show_count DESC, b.date ASC, b.start_time ASC
             LIMIT 12",
            ['b' => $this->businessId]
        );

        $upcoming = array_map(function (array $booking): array {
            $total = max(1, (int) ($booking['total_bookings'] ?? 0));
            $risk = min(99, 15 + ((int) ($booking['no_show_count'] ?? 0) * 18));
            if ($total >= 5 && (int) ($booking['no_show_count'] ?? 0) === 0) $risk = 8;
            $booking['risk_score'] = $risk;
            $booking['risk_action'] = $risk >= 60
                ? 'Pedir seña y confirmar 24 h antes'
                : ($risk >= 35 ? 'Confirmar por WhatsApp' : 'Recordatorio estándar');
            return $booking;
        }, $upcoming);

        return [
            'risky_clients' => $riskyClients,
            'upcoming_risk' => $upcoming,
        ];
    }

    private function automationPlaybooks(): array
    {
        $waitlistCount = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM waitlist_entries WHERE business_id = :b AND status = 'PENDING'",
            ['b' => $this->businessId]
        );
        $pendingPayments = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM bookings WHERE business_id = :b AND status = 'PENDING_PAYMENT'",
            ['b' => $this->businessId]
        );

        return [
            [
                'name' => 'Relleno de cancelaciones',
                'status' => $waitlistCount > 0 ? 'Activo' : 'Listo',
                'impact' => $waitlistCount . ' clientes esperando',
                'description' => 'Cuando se libera un horario, avisar primero a lista de espera.',
            ],
            [
                'name' => 'Señas con vencimiento',
                'status' => $pendingPayments > 0 ? 'Trabajando' : 'Listo',
                'impact' => $pendingPayments . ' pagos pendientes',
                'description' => 'Reserva el slot por tiempo limitado y libera automáticamente si no pagan.',
            ],
            [
                'name' => 'Recuperación de inactivos',
                'status' => 'Sugerido',
                'impact' => 'Campaña WhatsApp',
                'description' => 'Enviar propuesta a quienes no vuelven hace más de 45 días.',
            ],
            [
                'name' => 'Confirmación anti no-show',
                'status' => 'Sugerido',
                'impact' => 'Menos ausencias',
                'description' => 'Subir intensidad de confirmación según historial del cliente.',
            ],
        ];
    }

    private function locationReadiness(): array
    {
        $business = Business::find($this->businessId) ?? [];
        $configured = array_filter([
            $business['address'] ?? null,
            $business['city'] ?? null,
            $business['phone'] ?? null,
            $business['whatsapp'] ?? null,
        ]);

        return [
            'current_location' => [
                'name' => $business['name'] ?? '',
                'city' => $business['city'] ?? '',
                'address' => $business['address'] ?? '',
                'whatsapp' => $business['whatsapp'] ?? '',
            ],
            'readiness_score' => (int) round((count($configured) / 4) * 100),
            'needs_schema' => true,
            'next_step' => 'Para multi-sucursal real hay que agregar tabla de sucursales y asociar profesionales, servicios, horarios y reservas.',
        ];
    }

    private function campaignPlaybooks(int $inactiveCount, int $vipCount, int $newLeadCount): array
    {
        return [
            [
                'segment' => 'Clientes inactivos',
                'count' => $inactiveCount,
                'goal' => 'Recuperar turnos esta semana',
                'message' => 'Hola {nombre}, hace un tiempo no te vemos. Tenemos horarios disponibles esta semana para {servicio}. ¿Querés que te pase opciones?',
            ],
            [
                'segment' => 'Clientes frecuentes',
                'count' => $vipCount,
                'goal' => 'Fidelizar y subir ticket',
                'message' => 'Hola {nombre}, te guardamos prioridad para los mejores horarios de esta semana. También podés sumar un servicio complementario.',
            ],
            [
                'segment' => 'Nuevos clientes',
                'count' => $newLeadCount,
                'goal' => 'Conseguir segunda visita',
                'message' => 'Hola {nombre}, gracias por elegirnos. Si querés, te ayudo a reservar tu próximo turno en dos mensajes.',
            ],
        ];
    }

    private function clientNextAction(int $risk, ?int $daysSinceLast, ?string $favoriteService): string
    {
        if ($risk >= 65) return 'Pedir seña y confirmar manualmente si el turno es de alto valor.';
        if ($daysSinceLast !== null && $daysSinceLast > 45) {
            return 'Enviar campaña de reactivación' . ($favoriteService ? ' con foco en ' . $favoriteService : '') . '.';
        }
        if ($favoriteService) return 'Ofrecer próximo horario disponible para ' . $favoriteService . '.';
        return 'Mantener recordatorio automático y completar datos de contacto.';
    }
}
