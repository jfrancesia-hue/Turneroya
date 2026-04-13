<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Client
{
    public static function allByBusiness(string $businessId, string $search = ''): array
    {
        if ($search) {
            return Database::fetchAll(
                'SELECT * FROM clients WHERE business_id = :b AND (name ILIKE :q OR phone ILIKE :q OR email ILIKE :q) ORDER BY name ASC LIMIT 200',
                ['b' => $businessId, 'q' => '%' . $search . '%']
            );
        }
        return Database::fetchAll(
            'SELECT * FROM clients WHERE business_id = :b ORDER BY name ASC LIMIT 200',
            ['b' => $businessId]
        );
    }

    public static function find(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM clients WHERE id = :id', ['id' => $id]);
    }

    public static function findByPhoneOrWhatsapp(string $businessId, string $phone): ?array
    {
        $normalized = self::normalizePhone($phone);
        return Database::fetchOne(
            'SELECT * FROM clients
             WHERE business_id = :b
               AND (phone = :p OR whatsapp_number = :p)
             LIMIT 1',
            ['b' => $businessId, 'p' => $normalized]
        );
    }

    public static function findOrCreate(string $businessId, string $phone, string $name = ''): array
    {
        $existing = self::findByPhoneOrWhatsapp($businessId, $phone);
        if ($existing) return $existing;
        $id = self::create([
            'business_id' => $businessId,
            'name' => $name ?: 'Cliente ' . substr($phone, -4),
            'phone' => self::normalizePhone($phone),
            'whatsapp_number' => self::normalizePhone($phone),
        ]);
        return self::find($id) ?? [];
    }

    public static function create(array $data): string
    {
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = '{' . implode(',', array_map(fn($t) => '"' . str_replace('"', '\"', $t) . '"', $data['tags'])) . '}';
        }
        return Database::insert('clients', $data);
    }

    public static function update(string $id, array $data): int
    {
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = '{' . implode(',', array_map(fn($t) => '"' . str_replace('"', '\"', $t) . '"', $data['tags'])) . '}';
        }
        return Database::update('clients', $id, $data);
    }

    public static function delete(string $id): int
    {
        return Database::delete('clients', $id);
    }

    public static function incrementBookings(string $id): void
    {
        Database::query(
            'UPDATE clients SET total_bookings = total_bookings + 1, last_visit = NOW(), updated_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }

    public static function incrementNoShow(string $id): void
    {
        Database::query(
            'UPDATE clients SET no_show_count = no_show_count + 1, updated_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }

    public static function normalizePhone(string $phone): string
    {
        // Remover whatsapp:, espacios, guiones, paréntesis
        $phone = preg_replace('/^whatsapp:/', '', $phone);
        $phone = preg_replace('/[^\d+]/', '', (string) $phone);
        return (string) $phone;
    }
}
