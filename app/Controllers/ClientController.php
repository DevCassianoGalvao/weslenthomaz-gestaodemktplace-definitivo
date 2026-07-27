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
            'marketplaceAccounts' => [],
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
        $marketplaceAccounts = $this->parseMarketplaceAccounts($_POST);
        if (empty($marketplaceAccounts)) {
            $errors['marketplace_accounts'] = 'Cadastre pelo menos uma conta de marketplace.';
        }

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
                'selectedMarketplaceIds' => array_map('intval', array_column($marketplaceAccounts, 'marketplace_id')),
                'marketplaceAccounts' => $marketplaceAccounts,
                'marketplaces' => Marketplace::allActive(),
                'errors' => $errors,
                'old' => array_merge($data, ['account_name' => $accountName, 'account_email' => $accountEmail]),
            ]);
            return;
        }

        $clientId = Client::create($data['name'], $data['slug'], $data['logo_url'], $data['brand_color'], $data);
        Client::syncMarketplaceAccounts($clientId, $marketplaceAccounts);

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
            'marketplaceAccounts' => Client::marketplaceAccounts((int) $id),
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
        $marketplaceAccounts = $this->parseMarketplaceAccounts($_POST);
        if (empty($marketplaceAccounts)) {
            $errors['marketplace_accounts'] = 'Cadastre pelo menos uma conta de marketplace.';
        }

        $status = $_POST['status'] ?? 'active';
        if (!in_array($status, ['active', 'paused', 'archived'], true)) {
            $errors['status'] = 'Status inválido.';
        }

        if (!empty($errors)) {
            View::render('clients/form', [
                'mode' => 'edit',
                'client' => array_merge($client, $data, ['status' => $status]),
                'selectedMarketplaceIds' => array_map('intval', array_column($marketplaceAccounts, 'marketplace_id')),
                'marketplaceAccounts' => $marketplaceAccounts,
                'marketplaces' => Marketplace::allActive(),
                'errors' => $errors,
                'old' => [],
                'accountUser' => Client::accountUser($clientId),
            ]);
            return;
        }

        Client::update($clientId, $data['name'], $data['slug'], $data['logo_url'], $data['brand_color'], $status, $data);
        Client::syncMarketplaceAccounts($clientId, $marketplaceAccounts);

        header('Location: ' . url('/clients'));
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

        foreach (['website_url', 'instagram_url', 'facebook_url', 'tiktok_url'] as $field) {
            $value = trim($input[$field] ?? '');
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[$field] = 'URL invalida.';
            }
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'logo_url' => $logoUrl,
            'brand_color' => $brandColor,
            'website_url' => trim($input['website_url'] ?? ''),
            'instagram_url' => trim($input['instagram_url'] ?? ''),
            'facebook_url' => trim($input['facebook_url'] ?? ''),
            'tiktok_url' => trim($input['tiktok_url'] ?? ''),
            'whatsapp' => trim($input['whatsapp'] ?? ''),
            'notes' => trim($input['notes'] ?? ''),
        ];

        return [$data, $errors];
    }

    private function parseMarketplaceAccounts(array $input): array
    {
        $raw = $input['marketplace_accounts'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $accounts = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }

            $marketplaceId = (int) ($row['marketplace_id'] ?? 0);
            $accountName = trim((string) ($row['account_name'] ?? ''));
            if ($marketplaceId <= 0 && $accountName === '') {
                continue;
            }

            $accounts[] = [
                'id' => !empty($row['id']) ? (int) $row['id'] : null,
                'marketplace_id' => $marketplaceId,
                'account_name' => $accountName,
                'account_identifier' => trim((string) ($row['account_identifier'] ?? '')),
            ];
        }

        return array_values($accounts);
    }
}
