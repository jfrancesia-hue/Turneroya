<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class BotConversation
{
    public static function findOrCreate(string $businessId, string $whatsappNumber): array
    {
        $existing = Database::fetchOne(
            'SELECT * FROM bot_conversations WHERE business_id = :b AND whatsapp_number = :w',
            ['b' => $businessId, 'w' => $whatsappNumber]
        );
        if ($existing) return $existing;

        $id = Database::insert('bot_conversations', [
            'business_id' => $businessId,
            'whatsapp_number' => $whatsappNumber,
            'state' => '{}',
            'messages' => '[]',
        ]);
        return Database::fetchOne('SELECT * FROM bot_conversations WHERE id = :id', ['id' => $id]) ?? [];
    }

    public static function updateState(string $id, array $state, array $messages): void
    {
        Database::query(
            'UPDATE bot_conversations SET state = :s, messages = :m, last_message_at = NOW() WHERE id = :id',
            [
                's' => json_encode($state, JSON_UNESCAPED_UNICODE),
                'm' => json_encode($messages, JSON_UNESCAPED_UNICODE),
                'id' => $id,
            ]
        );
    }

    public static function reset(string $id): void
    {
        Database::query(
            "UPDATE bot_conversations SET state = '{}', messages = '[]' WHERE id = :id",
            ['id' => $id]
        );
    }
}
