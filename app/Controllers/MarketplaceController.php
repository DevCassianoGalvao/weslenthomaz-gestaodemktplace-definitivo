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
            echo 'Sessao expirada, volte e tente novamente.';
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
            $errors['color'] = 'Cor invalida - use o formato #RRGGBB.';
        }

        $slug = Client::slugify($name);
        if ($slug !== '' && Marketplace::slugExists($slug, $marketplaceId)) {
            $errors['name'] = 'Ja existe outro marketplace com esse nome.';
        }

        if (!empty($errors)) {
            View::render('marketplaces/index', [
                'marketplaces' => Marketplace::all(),
                'errors' => $errors,
                'old' => [],
            ]);
            return;
        }

        try {
            Marketplace::update($marketplaceId, $name, $slug, $color ?: null);
        } catch (\PDOException $exception) {
            error_log(sprintf(
                'Marketplace update failed (id=%d, slug=%s): %s',
                $marketplaceId,
                $slug,
                $exception->getMessage()
            ));
            $errors['name'] = 'Nao foi possivel salvar este marketplace. Verifique o log do servidor.';
        }

        if (!empty($errors)) {
            View::render('marketplaces/index', [
                'marketplaces' => Marketplace::all(),
                'errors' => $errors,
                'old' => ['name' => $name, 'color' => $color],
            ]);
            return;
        }

        header('Location: ' . url('/marketplaces'));
        exit;
    }
}
