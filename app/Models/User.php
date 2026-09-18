<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role, client_id FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, role, client_id FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByIdWithPassword(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role, client_id FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function create(string $name, string $email, string $password, string $role, ?int $clientId = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role, client_id) VALUES (:name, :email, :password_hash, :role, :client_id)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'client_id' => $clientId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function updateCredentials(int $id, string $email, ?string $password = null): void
    {
        $sql = 'UPDATE users SET email = :email';
        $params = ['email' => $email, 'id' => $id];
        if ($password !== null && $password !== '') {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }
        $sql .= ' WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);
    }

    public static function emailExistsExcept(string $email, int $userId): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id != :id');
        $stmt->execute(['email' => $email, 'id' => $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function collaborators(): array
    {
        return Database::connection()->query(
            "SELECT id, name, email, created_at FROM users WHERE role = 'operator' ORDER BY name"
        )->fetchAll();
    }

    private static ?bool $marketplaceScopingSupported = null;

    public static function supportsMarketplaceScoping(): bool
    {
        if (self::$marketplaceScopingSupported !== null) {
            return self::$marketplaceScopingSupported;
        }

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
        );
        $stmt->execute(['table_name' => 'user_marketplaces']);

        return self::$marketplaceScopingSupported = (int) $stmt->fetchColumn() > 0;
    }

    /**
     * IDs dos marketplaces aos quais o usuário está restrito. Lista vazia significa
     * "sem restrição" (enxerga todos) — é o estado padrão de qualquer colaborador
     * até que um admin marque canais específicos para ele (PRD: permissão por marketplace).
     */
    public static function marketplaceIds(int $userId): array
    {
        if (!self::supportsMarketplaceScoping()) {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT marketplace_id FROM user_marketplaces WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param int[] $marketplaceIds */
    public static function syncMarketplaces(int $userId, array $marketplaceIds): void
    {
        if (!self::supportsMarketplaceScoping()) {
            return;
        }

        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM user_marketplaces WHERE user_id = :user_id')->execute(['user_id' => $userId]);

        if (empty($marketplaceIds)) {
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO user_marketplaces (user_id, marketplace_id) VALUES (:user_id, :marketplace_id)');
        foreach (array_unique(array_map('intval', $marketplaceIds)) as $marketplaceId) {
            $stmt->execute(['user_id' => $userId, 'marketplace_id' => $marketplaceId]);
        }
    }

    /** Colaboradores com os marketplaces atribuídos, para a tela de gestão de equipe. */
    public static function collaboratorsWithMarketplaces(): array
    {
        $collaborators = self::collaborators();
        if (!self::supportsMarketplaceScoping() || empty($collaborators)) {
            foreach ($collaborators as &$collaborator) {
                $collaborator['marketplace_ids'] = [];
            }
            return $collaborators;
        }

        $stmt = Database::connection()->query(
            'SELECT um.user_id, m.id, m.name
             FROM user_marketplaces um
             INNER JOIN marketplaces m ON m.id = um.marketplace_id
             ORDER BY m.name'
        );

        $byUser = [];
        foreach ($stmt->fetchAll() as $row) {
            $byUser[(int) $row['user_id']]['ids'][] = (int) $row['id'];
            $byUser[(int) $row['user_id']]['names'][] = $row['name'];
        }

        foreach ($collaborators as &$collaborator) {
            $userId = (int) $collaborator['id'];
            $collaborator['marketplace_ids'] = $byUser[$userId]['ids'] ?? [];
            $collaborator['marketplace_names'] = $byUser[$userId]['names'] ?? [];
        }

        return $collaborators;
    }

    /** Senha inicial gerada pelo sistema (PRD 5.3) — evita caracteres ambíguos (0/O, 1/l/I). */
    public static function generatePassword(int $length = 12): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }
}
