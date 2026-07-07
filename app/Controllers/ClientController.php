<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Models\Client;
use App\Models\Marketplace;
use App\Models\User;

class ClientController
{
    public function index(): void
    {
        View::render('clients/index', [
            'clients' => Client::all(),
        ]);
    }

    public function create(): void
    {
        View::render('clients/form', [
            'mode' => 'create',
            'client' => null,
            'selectedMarketplaceIds' => [],
            'marketplaces' => Marketplace::allActive(),
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

        [$data, $errors] = $this->validate($_POST);

        // E-mail da conta do cliente final é obrigatório na criação (PRD 5.3).
        $accountEmail = trim($_POST['account_email'] ?? '');
        $accountName = trim($_POST['account_name'] ?? '');
        if ($accountName === '') {
            $errors['account_name'] = 'Informe o nome do responsável pela conta de acesso.';
        }
        if ($accountEmail === '' || !filter_var($accountEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['account_email'] = 'Informe um e-mail válido para a conta de acesso.';
        } elseif (User::emailExists($accountEmail)) {
            $errors['account_email'] = 'Já existe uma conta com este e-mail.';
        }

        if (!empty($errors)) {
            View::render('clients/form', [
                'mode' => 'create',
                'client' => null,
                'selectedMarketplaceIds' => array_map('intval', $_POST['marketplaces'] ?? []),
                'marketplaces' => Marketplace::allActive(),
                'errors' => $errors,
                'old' => array_merge($data, ['account_name' => $accountName, 'account_email' => $accountEmail]),
            ]);
            return;
        }

        $clientId = Client::create($data['name'], $data['slug'], $data['logo_url'], $data['brand_color']);
        Client::syncMarketplaces($clientId, array_map('intval', $_POST['marketplaces'] ?? []));

        $generatedPassword = User::generatePassword();
        User::create($accountName, $accountEmail, $generatedPassword, 'client', $clientId);

        View::render('clients/created', [
            'client' => Client::find($clientId),
            'accountName' => $accountName,
            'accountEmail' => $accountEmail,
            'generatedPassword' => $generatedPassword,
        ]);
    }

    public function edit(string $id): void
    {
        $client = Client::find((int) $id);
        if (!$client) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        View::render('clients/form', [
            'mode' => 'edit',
            'client' => $client,
            'selectedMarketplaceIds' => Client::marketplaceIds((int) $id),
            'marketplaces' => Marketplace::allActive(),
            'errors' => [],
            'old' => [],
            'accountUser' => Client::accountUser((int) $id),
        ]);
    }

    public function update(string $id): void
    {
        $clientId = (int) $id;
        $client = Client::find($clientId);
        if (!$client) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }

        [$data, $errors] = $this->validate($_POST, $clientId);

        $status = $_POST['status'] ?? 'active';
        if (!in_array($status, ['active', 'paused', 'archived'], true)) {
            $errors['status'] = 'Status inválido.';
        }

        if (!empty($errors)) {
            View::render('clients/form', [
                'mode' => 'edit',
                'client' => array_merge($client, $data, ['status' => $status]),
                'selectedMarketplaceIds' => array_map('intval', $_POST['marketplaces'] ?? []),
                'marketplaces' => Marketplace::allActive(),
                'errors' => $errors,
                'old' => [],
                'accountUser' => Client::accountUser($clientId),
            ]);
            return;
        }

        Client::update($clientId, $data['name'], $data['slug'], $data['logo_url'], $data['brand_color'], $status);
        Client::syncMarketplaces($clientId, array_map('intval', $_POST['marketplaces'] ?? []));

        header('Location: /clients');
        exit;
    }

    private function validate(array $input, ?int $excludeId = null): array
    {
        $errors = [];

        $name = trim($input['name'] ?? '');
        if ($name === '') {
            $errors['name'] = 'Informe o nome do cliente.';
        }

        $slugInput = trim($input['slug'] ?? '');
        $slug = $slugInput !== '' ? Client::slugify($slugInput) : Client::slugify($name);
        if ($slug === '') {
            $errors['slug'] = 'Não foi possível gerar um identificador (slug) a partir do nome.';
        } elseif (Client::slugExists($slug, $excludeId)) {
            $errors['slug'] = 'Já existe um cliente com este identificador (slug). Ajuste manualmente.';
        }

        $brandColor = trim($input['brand_color'] ?? '');
        if ($brandColor !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor)) {
            $errors['brand_color'] = 'Cor inválida — use o formato #RRGGBB.';
        }

        $logoUrl = trim($input['logo_url'] ?? '');
        if ($logoUrl !== '' && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            $errors['logo_url'] = 'URL do logo inválida.';
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'logo_url' => $logoUrl,
            'brand_color' => $brandColor,
        ];

        return [$data, $errors];
    }
}
