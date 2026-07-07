<?php

namespace App\Models;

use App\Core\Database;

class Client
{
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT c.id, c.name, c.slug, c.logo_url, c.brand_color, c.status, c.created_at,
                    COUNT(cm.marketplace_id) AS marketplace_count
             FROM clients c
             LEFT JOIN client_marketplaces cm ON cm.client_id = c.id
             GROUP BY c.id
             ORDER BY c.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $client = $stmt->fetch();

        return $client ?: null;
    }

    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM clients WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params['excludeId'] = $excludeId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(string $name, string $slug, ?string $logoUrl, ?string $brandColor): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO clients (name, slug, logo_url, brand_color, status) VALUES (:name, :slug, :logo_url, :brand_color, "active")'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'logo_url' => $logoUrl ?: null,
            'brand_color' => $brandColor ?: null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $name, string $slug, ?string $logoUrl, ?string $brandColor, string $status): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE clients SET name = :name, slug = :slug, logo_url = :logo_url, brand_color = :brand_color, status = :status WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'logo_url' => $logoUrl ?: null,
            'brand_color' => $brandColor ?: null,
            'status' => $status,
            'id' => $id,
        ]);
    }

    public static function marketplaceIds(int $clientId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT marketplace_id FROM client_marketplaces WHERE client_id = :client_id'
        );
        $stmt->execute(['client_id' => $clientId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** Marketplaces vinculados a este cliente (objetos completos, usados na matriz de lançamento). */
    public static function marketplaces(int $clientId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.id, m.name, m.slug, m.color
             FROM marketplaces m
             INNER JOIN client_marketplaces cm ON cm.marketplace_id = m.id
             WHERE cm.client_id = :client_id
             ORDER BY m.name'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    /** @param int[] $marketplaceIds */
    public static function syncMarketplaces(int $clientId, array $marketplaceIds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM client_marketplaces WHERE client_id = :client_id')
            ->execute(['client_id' => $clientId]);

        if (empty($marketplaceIds)) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO client_marketplaces (client_id, marketplace_id) VALUES (:client_id, :marketplace_id)'
        );
        foreach ($marketplaceIds as $marketplaceId) {
            $stmt->execute(['client_id' => $clientId, 'marketplace_id' => (int) $marketplaceId]);
        }
    }

    public static function accountUser(int $clientId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT id, name, email FROM users WHERE client_id = :client_id AND role = 'client' LIMIT 1"
        );
        $stmt->execute(['client_id' => $clientId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
