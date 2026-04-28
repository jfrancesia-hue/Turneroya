<?php
declare(strict_types=1);

namespace TurneroYa\Services;

/**
 * Lógica pura de matching entre una entry de waitlist y un slot liberado.
 * Extraída como helper para poder testearla sin tocar la base de datos.
 */
final class WaitlistMatcher
{
    /**
     * Devuelve true si el slot (servicio + profesional opcional + fecha + hora)
     * matchea con los criterios de la entry de waitlist.
     *
     * @param array $entry registro de waitlist_entries
     */
    public static function matches(
        array $entry,
        string $serviceId,
        ?string $professionalId,
        string $date,
        string $startTime
    ): bool {
        if (($entry['service_id'] ?? null) !== $serviceId) {
            return false;
        }

        // Profesional: si la entry tiene profesional específico, debe matchear;
        // si NULL, acepta cualquiera.
        if (!empty($entry['professional_id']) && $entry['professional_id'] !== $professionalId) {
            return false;
        }

        // Fecha
        if ($date < ($entry['preferred_date_from'] ?? '')) {
            return false;
        }
        if (!empty($entry['preferred_date_to']) && $date > $entry['preferred_date_to']) {
            return false;
        }

        // Hora
        if (!empty($entry['preferred_time_from']) && $startTime < $entry['preferred_time_from']) {
            return false;
        }
        if (!empty($entry['preferred_time_to']) && $startTime > $entry['preferred_time_to']) {
            return false;
        }

        return true;
    }
}
