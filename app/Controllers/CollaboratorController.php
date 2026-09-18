<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Models\Marketplace;
use App\Models\User;

class CollaboratorController
{
    public function index(): void
    {
        View::render('collaborators/index', [
            'collaborators' => User::collaboratorsWithMarketplaces(),
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

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $marketplaceIds = array_map('intval', $_POST['marketplace_ids'] ?? []);
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Informe o nome do colaborador.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um e-mail válido.';
        } elseif (User::emailExists($email)) {
            $errors['email'] = 'Já existe uma conta com este e-mail.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'A senha deve ter pelo menos 8 caracteres.';
        }

        if (!empty($errors)) {
            View::render('collaborators/index', [
                'collaborators' => User::collaboratorsWithMarketplaces(),
                'marketplaces' => Marketplace::allActive(),
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email, 'marketplace_ids' => $marketplaceIds],
            ]);
            return;
        }

        $userId = User::create($name, $email, $password, 'operator');
        User::syncMarketplaces($userId, $marketplaceIds);
        header('Location: ' . url('/collaborators?created=1'));
        exit;
    }

    /** Atualiza os marketplaces permitidos de um colaborador já existente. */
    public function updateMarketplaces(string $id): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }

        $userId = (int) $id;
        $user = User::findById($userId);
        if (!$user || $user['role'] !== 'operator') {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $marketplaceIds = array_map('intval', $_POST['marketplace_ids'] ?? []);
        User::syncMarketplaces($userId, $marketplaceIds);

        header('Location: ' . url('/collaborators?permissions_updated=1'));
        exit;
    }
}
