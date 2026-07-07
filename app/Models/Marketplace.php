<?php

namespace App\Models;

use App\Core\Database;

class Marketplace
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, slug, color, icon, is_active FROM marketplaces ORDER BY name')
            ->fetchAll();
    }

    public static function allActive(): array
    {
        return Database::connection()
            ->query('SELECT id, name, slug, color, icon FROM marketplaces WHERE is_active = 1 ORDER BY name')
            ->fetchAll();
    }

    public static function slugExists(string $slug): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM marketplaces WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(string $name, string $slug, ?string $color): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO marketplaces (name, slug, color, is_active) VALUES (:name, :slug, :color, 1)'
        );
        $stmt->execute(['name' => $name, 'slug' => $slug, 'color' => $color]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function toggleActive(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE marketplaces SET is_active = NOT is_active WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
