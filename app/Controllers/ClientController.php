<?php

namespace App\Controllers;

use App\Core\Auth;
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
        [$uploadedLogo, $uploadError] = $this->handleLogoUpload($_FILES['logo_file'] ?? null, $data['slug']);
        if ($uploadedLogo !== null) {
            $data['logo_url'] = $uploadedLogo;
        }
        if ($uploadError !== null) {
            $errors['logo_file'] = $uploadError;
        }
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
        [$uploadedLogo, $uploadError] = $this->handleLogoUpload($_FILES['logo_file'] ?? null, $data['slug'], $client['logo_url'] ?? null);
        if ($uploadedLogo !== null) {
            $data['logo_url'] = $uploadedLogo;
        } elseif (trim($_POST['logo_url'] ?? '') === '' && !empty($client['logo_url'])) {
            $data['logo_url'] = $client['logo_url'];
        }
        if ($uploadError !== null) {
            $errors['logo_file'] = $uploadError;
        }
        $marketplaceAccounts = $this->parseMarketplaceAccounts($_POST);
        if (empty($marketplaceAccounts)) {
            $errors['marketplace_accounts'] = 'Cadastre pelo menos uma conta de marketplace.';
        }

        $status = Auth::isAdmin() ? ($_POST['status'] ?? 'active') : ($client['status'] ?? 'active');
        if (Auth::isAdmin() && !in_array($status, ['active', 'paused'], true)) {
            $errors['status'] = 'Status inválido.';
        }

        $accountUser = Client::accountUser($clientId);
        $accountEmail = trim($_POST['account_email'] ?? ($accountUser['email'] ?? ''));
        $accountPassword = (string) ($_POST['account_password'] ?? '');
        if ($accountUser && ($accountEmail === '' || !filter_var($accountEmail, FILTER_VALIDATE_EMAIL))) {
            $errors['account_email'] = 'Informe um e-mail válido para a conta de acesso.';
        } elseif ($accountUser && User::emailExistsExcept($accountEmail, (int) $accountUser['id'])) {
            $errors['account_email'] = 'Já existe uma conta com este e-mail.';
        }
        if ($accountPassword !== '' && strlen($accountPassword) < 8) {
            $errors['account_password'] = 'A nova senha deve ter pelo menos 8 caracteres.';
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
                'accountUser' => array_merge($accountUser ?? [], ['email' => $accountEmail]),
            ]);
            return;
        }

        Client::update($clientId, $data['name'], $data['slug'], $data['logo_url'], $data['brand_color'], $status, $data);
        Client::syncMarketplaceAccounts($clientId, $marketplaceAccounts);
        if ($accountUser) {
            User::updateCredentials((int) $accountUser['id'], $accountEmail, $accountPassword !== '' ? $accountPassword : null);
        }

        header('Location: ' . url('/clients'));
        exit;
    }

    public function delete(string $id): void
    {
        $clientId = (int) $id;
        if (!Client::find($clientId)) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }
        Client::delete($clientId);
        header('Location: ' . url('/clients?deleted=1'));
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

        $logoUrl = $this->normalizeUrl($input['logo_url'] ?? '');
        if ($logoUrl !== '' && !$this->isInternalPath($logoUrl) && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            $errors['logo_url'] = 'URL do logo inválida.';
        }

        foreach (['website_url', 'instagram_url', 'facebook_url', 'tiktok_url'] as $field) {
            $value = $this->normalizeUrl($input[$field] ?? '');
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[$field] = 'URL inválida.';
            }
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'logo_url' => $logoUrl,
            'brand_color' => $brandColor,
            'website_url' => $this->normalizeUrl($input['website_url'] ?? ''),
            'instagram_url' => $this->normalizeUrl($input['instagram_url'] ?? ''),
            'facebook_url' => $this->normalizeUrl($input['facebook_url'] ?? ''),
            'tiktok_url' => $this->normalizeUrl($input['tiktok_url'] ?? ''),
            'whatsapp' => trim($input['whatsapp'] ?? ''),
            'notes' => trim($input['notes'] ?? ''),
        ];

        return [$data, $errors];
    }

    private function normalizeUrl(string $value): string
    {
        $value = trim($value);
        if (preg_match('#^https?://paineldemetricas(/.*)?$#i', $value, $matches)) {
            return '/paineldemetricas' . ($matches[1] ?? '');
        }
        if ($value !== '' && !$this->isInternalPath($value) && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            $value = 'https://' . $value;
        }
        return $value;
    }

    private function isInternalPath(string $value): bool
    {
        return str_starts_with($value, '/');
    }

    private function handleLogoUpload(?array $file, string $slug, ?string $currentUrl = null): array
    {
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return [null, 'Não foi possível receber o arquivo do logo.'];
        }

        if ((int) ($file['size'] ?? 0) > 3 * 1024 * 1024) {
            return [null, 'O logo deve ter no máximo 3 MB.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return [null, 'O arquivo enviado não pôde ser validado. Tente novamente.'];
        }

        $imageInfo = @getimagesize($tmpName);
        $imageTypes = [
            IMAGETYPE_JPEG => ['extension' => 'jpg'],
            IMAGETYPE_PNG => ['extension' => 'png'],
        ];
        if (defined('IMAGETYPE_WEBP')) {
            $imageTypes[IMAGETYPE_WEBP] = ['extension' => 'webp'];
        }

        $detectedType = is_array($imageInfo) ? (int) ($imageInfo[2] ?? 0) : 0;
        $imageType = $imageTypes[$detectedType] ?? null;
        if (!$imageType) {
            return [null, 'Formato inválido. Envie PNG, JPG ou WEBP.'];
        }

        $directory = __DIR__ . '/../../public/uploads/clients';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return [null, 'Não foi possível preparar o armazenamento do logo.'];
        }

        $safeSlug = preg_replace('/[^a-z0-9-]+/i', '-', $slug) ?: 'cliente';
        try {
            $filename = trim($safeSlug, '-') . '-' . bin2hex(random_bytes(8)) . '.' . $imageType['extension'];
        } catch (\Throwable $exception) {
            return [null, 'Não foi possível gerar o nome do arquivo do logo.'];
        }
        $target = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpName, $target)) {
            return [null, 'Não foi possível salvar o logo enviado.'];
        }

        return [url('/uploads/clients/' . $filename), null];
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
