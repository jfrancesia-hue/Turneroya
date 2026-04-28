<?php
declare(strict_types=1);

namespace TurneroYa\Services;

/**
 * Formato canónico de payloads de botones WhatsApp.
 * Convención: "tya:{action}:{bookingId}"
 *
 * Estos payloads son los que se mandan en el campo "ContentVariables"
 * al crear un mensaje con Content Template, y los que vuelven en el campo
 * "ButtonPayload" del webhook cuando el usuario aprieta un botón.
 */
final class WhatsAppButtonPayloads
{
    public const PREFIX = 'tya:';
    public const ACTION_CONFIRM = 'confirm';
    public const ACTION_CANCEL = 'cancel';
    public const ACTION_RESCHEDULE = 'reschedule';

    public static function build(string $action, string $bookingId): string
    {
        return self::PREFIX . $action . ':' . $bookingId;
    }

    /**
     * @return array{action: string, booking_id: string}|null  null si no es un payload nuestro
     */
    public static function parse(string $raw): ?array
    {
        if (!str_starts_with($raw, self::PREFIX)) return null;
        $rest = substr($raw, strlen(self::PREFIX));
        $parts = explode(':', $rest, 2);
        if (count($parts) !== 2) return null;
        [$action, $bookingId] = $parts;
        if (!in_array($action, [self::ACTION_CONFIRM, self::ACTION_CANCEL, self::ACTION_RESCHEDULE], true)) {
            return null;
        }
        if ($bookingId === '') return null;
        return ['action' => $action, 'booking_id' => $bookingId];
    }
}
