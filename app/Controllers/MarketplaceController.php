<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Models\Client;
use App\Models\Marketplace;

class MarketplaceController
{
    public function index(): void
    {
        View::render('marketplaces/index', [
            'marketplaces' => Marketplace::all(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Informe o nome do marketplace.';
        }

        if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors['color'] = 'Cor inválida — use o formato #RRGGBB.';
        }

        $slug = Client::slugify($name);
        if ($slug !== '' && Marketplace::slugExists($slug)) {
            $errors['name'] = 'Já existe um marketplace com esse nome.';
        }

        if (!empty($errors)) {
            View::render('marketplaces/index', [
                'marketplaces' => Marketplace::all(),
                'errors' => $errors,
                'old' => ['name' => $name, 'color' => $color],
            ]);
            return;
        }

        Marketplace::create($name, $slug, $color ?: null);

        header('Location: ' . url('/marketplaces'));
        exit;
    }

    public function toggle(string $id): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }

        Marketplace::toggleActive((int) $id);

        header('Location: ' . url('/marketplaces'));
        exit;
    }

    public function update(string $id): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'SessÃ£o expirada, volte e tente novamente.';
            return;
        }

        $marketplaceId = (int) $id;
        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Informe o nome do marketplace.';
        }
        if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors['color'] = 'Cor invÃ¡lida â€” use o formato #RRGGBB.';
        }

        $slug = Client::slugify($name);
        if ($slug !== '' && Marketplace::slugExists($slug, $marketplaceId)) {
            $errors['name'] = 'JÃ¡ existe outro marketplace com esse nome.';
        }

        if (empty($errors)) {
            Marketplace::update($marketplaceId, $name, $slug, $color ?: null);
        }

        header('Location: ' . url('/marketplaces'));
        exit;
    }
}
