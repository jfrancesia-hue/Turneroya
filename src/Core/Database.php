<?php
declare(strict_types=1);

namespace TurneroYa\Core;

use PDO;
use PDOException;

/**
 * Conexión PostgreSQL con PDO (singleton).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $host = (string) Config::get('database.host', '127.0.0.1');
            $port = (string) Config::get('database.port', '5432');
            $db   = (string) Config::get('database.database', 'turneroya');
            $user = (string) Config::get('database.username', 'postgres');
            $pass = (string) Config::get('database.password', '');

            $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                ]);
                self::$pdo->exec("SET TIME ZONE 'America/Argentina/Buenos_Aires'");
            } catch (PDOException $e) {
                throw new \RuntimeException('Error al conectar con PostgreSQL: ' . $e->getMessage(), 0, $e);
            }
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data): string
    {
        $keys = array_keys($data);
        $cols = implode(', ', array_map(fn($k) => "\"$k\"", $keys));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", $keys));
        $sql = "INSERT INTO \"$table\" ($cols) VALUES ($placeholders) RETURNING id";
        return (string) self::query($sql, $data)->fetchColumn();
    }

    public static function update(string $table, string $id, array $data): int
    {
        $sets = implode(', ', array_map(fn($k) => "\"$k\" = :$k", array_keys($data)));
        $data['id'] = $id;
        $sql = "UPDATE \"$table\" SET $sets, \"updated_at\" = NOW() WHERE id = :id";
        return self::query($sql, $data)->rowCount();
    }

    public static function delete(string $table, string $id): int
    {
        return self::query("DELETE FROM \"$table\" WHERE id = :id", ['id' => $id])->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
