<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Models\User;

class CollaboratorController
{
    public function index(): void
    {
        View::render('collaborators/index', [
            'collaborators' => User::collaborators(),
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
                'collaborators' => User::collaborators(),
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email],
            ]);
            return;
        }

        User::create($name, $email, $password, 'operator');
        header('Location: ' . url('/collaborators?created=1'));
        exit;
    }
}
