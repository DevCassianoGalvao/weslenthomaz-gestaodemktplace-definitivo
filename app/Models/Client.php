<?php

namespace App\Models;

use App\Core\Database;

class Client
{
    public static function all(bool $activeOnly = false): array
    {
        $where = $activeOnly ? "WHERE c.status = 'active'" : '';
        return Database::connection()->query(
            'SELECT c.id, c.name, c.slug, c.logo_url, c.brand_color, c.status, c.created_at,
                    COUNT(cma.id) AS marketplace_count
             FROM clients c
             LEFT JOIN client_marketplace_accounts cma ON cma.client_id = c.id AND cma.is_active = 1
             ' . $where . '
             GROUP BY c.id
             ORDER BY c.name'
        )->fetchAll();
    }

    public static function delete(int $id): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM entry_history WHERE client_id = :client_id')->execute(['client_id' => $id]);
            $pdo->prepare('DELETE e FROM entries e INNER JOIN periods p ON p.id = e.period_id WHERE p.client_id = :client_id')->execute(['client_id' => $id]);
            $pdo->prepare('DELETE FROM periods WHERE client_id = :client_id')->execute(['client_id' => $id]);
            $pdo->prepare('DELETE FROM client_marketplaces WHERE client_id = :client_id')->execute(['client_id' => $id]);
            $pdo->prepare('DELETE FROM client_marketplace_accounts WHERE client_id = :client_id')->execute(['client_id' => $id]);
            $pdo->prepare('DELETE FROM users WHERE client_id = :client_id')->execute(['client_id' => $id]);
            $pdo->prepare('DELETE FROM clients WHERE id = :id')->execute(['id' => $id]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
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

    public static function create(string $name, string $slug, ?string $logoUrl, ?string $brandColor, array $profile = []): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO clients (name, slug, logo_url, brand_color, website_url, instagram_url, facebook_url, tiktok_url, whatsapp, notes, status)
             VALUES (:name, :slug, :logo_url, :brand_color, :website_url, :instagram_url, :facebook_url, :tiktok_url, :whatsapp, :notes, "active")'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'logo_url' => $logoUrl ?: null,
            'brand_color' => $brandColor ?: null,
            'website_url' => ($profile['website_url'] ?? '') ?: null,
            'instagram_url' => ($profile['instagram_url'] ?? '') ?: null,
            'facebook_url' => ($profile['facebook_url'] ?? '') ?: null,
            'tiktok_url' => ($profile['tiktok_url'] ?? '') ?: null,
            'whatsapp' => ($profile['whatsapp'] ?? '') ?: null,
            'notes' => ($profile['notes'] ?? '') ?: null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $name, string $slug, ?string $logoUrl, ?string $brandColor, string $status, array $profile = []): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE clients
             SET name = :name,
                 slug = :slug,
                 logo_url = :logo_url,
                 brand_color = :brand_color,
                 website_url = :website_url,
                 instagram_url = :instagram_url,
                 facebook_url = :facebook_url,
                 tiktok_url = :tiktok_url,
                 whatsapp = :whatsapp,
                 notes = :notes,
                 status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'logo_url' => $logoUrl ?: null,
            'brand_color' => $brandColor ?: null,
            'website_url' => ($profile['website_url'] ?? '') ?: null,
            'instagram_url' => ($profile['instagram_url'] ?? '') ?: null,
            'facebook_url' => ($profile['facebook_url'] ?? '') ?: null,
            'tiktok_url' => ($profile['tiktok_url'] ?? '') ?: null,
            'whatsapp' => ($profile['whatsapp'] ?? '') ?: null,
            'notes' => ($profile['notes'] ?? '') ?: null,
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

    /** Todas as contas vinculadas ao cliente, usadas na matriz de lançamentos. */
    public static function marketplaces(int $clientId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT cma.id,
                    cma.marketplace_id,
                    cma.account_name,
                    cma.account_identifier,
                    m.name AS marketplace_name,
                    m.slug,
                    m.color,
                    CONCAT(m.name, " - ", cma.account_name) AS name
             FROM client_marketplace_accounts cma
             INNER JOIN marketplaces m ON m.id = cma.marketplace_id
             WHERE cma.client_id = :client_id
             ORDER BY m.name, cma.account_name'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    public static function marketplaceAccounts(int $clientId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT cma.id, cma.client_id, cma.marketplace_id, cma.account_name, cma.account_identifier, cma.is_active,
                    m.name AS marketplace_name, m.color
             FROM client_marketplace_accounts cma
             INNER JOIN marketplaces m ON m.id = cma.marketplace_id
             WHERE cma.client_id = :client_id
             ORDER BY m.name, cma.account_name'
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

    public static function syncMarketplaceAccounts(int $clientId, array $accounts): void
    {
        $pdo = Database::connection();
        $keptIds = [];
        $linkedMarketplaceIds = [];
        $idsStmt = $pdo->prepare('SELECT id FROM client_marketplace_accounts WHERE client_id = :client_id');
        $idsStmt->execute(['client_id' => $clientId]);
        $allowedIds = array_map('intval', $idsStmt->fetchAll(\PDO::FETCH_COLUMN));

        $upsert = $pdo->prepare(
            'INSERT INTO client_marketplace_accounts (id, client_id, marketplace_id, account_name, account_identifier, is_active)
             VALUES (:id, :client_id, :marketplace_id, :account_name, :account_identifier, 1)
             ON DUPLICATE KEY UPDATE
                marketplace_id = VALUES(marketplace_id),
                account_name = VALUES(account_name),
                account_identifier = VALUES(account_identifier),
                is_active = 1'
        );

        foreach ($accounts as $account) {
            $marketplaceId = (int) ($account['marketplace_id'] ?? 0);
            $accountName = trim((string) ($account['account_name'] ?? ''));
            if ($marketplaceId <= 0 || $accountName === '') {
                continue;
            }

            $id = !empty($account['id']) ? (int) $account['id'] : null;
            if ($id !== null && !in_array($id, $allowedIds, true)) {
                $id = null;
            }
            $upsert->execute([
                'id' => $id,
                'client_id' => $clientId,
                'marketplace_id' => $marketplaceId,
                'account_name' => $accountName,
                'account_identifier' => trim((string) ($account['account_identifier'] ?? '')) ?: null,
            ]);

            $savedId = $id ?: (int) $pdo->lastInsertId();
            $keptIds[] = $savedId;
            $linkedMarketplaceIds[] = $marketplaceId;

            $pdo->prepare('UPDATE entries SET marketplace_id = :marketplace_id WHERE client_marketplace_account_id = :account_id')
                ->execute(['marketplace_id' => $marketplaceId, 'account_id' => $savedId]);
            $pdo->prepare('UPDATE entry_history SET marketplace_id = :marketplace_id WHERE client_marketplace_account_id = :account_id')
                ->execute(['marketplace_id' => $marketplaceId, 'account_id' => $savedId]);
        }

        if (!empty($keptIds)) {
            $placeholders = implode(',', array_fill(0, count($keptIds), '?'));
            $stmt = $pdo->prepare(
                "UPDATE client_marketplace_accounts SET is_active = 0 WHERE client_id = ? AND id NOT IN ({$placeholders})"
            );
            $stmt->execute(array_merge([$clientId], $keptIds));
        } else {
            $pdo->prepare('UPDATE client_marketplace_accounts SET is_active = 0 WHERE client_id = :client_id')
                ->execute(['client_id' => $clientId]);
        }

        self::syncMarketplaces($clientId, array_values(array_unique($linkedMarketplaceIds)));
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

    /**
     * Transliteração explícita (não usa iconv//TRANSLIT) porque o comportamento
     * varia entre plataformas — no Windows ele insere marcas diacríticas em vez
     * de removê-las (ex: "eletrônicos" virava "eletr^onicos").
     */
    public static function slugify(string $text): string
    {
        $text = trim($text);
        $text = function_exists('mb_strtolower')
            ? mb_strtolower($text, 'UTF-8')
            : strtolower($text);
        $accents = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n', 'ý' => 'y',
        ];
        $text = strtr($text, $accents);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
