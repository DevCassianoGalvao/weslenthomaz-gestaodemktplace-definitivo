<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: ' . url('/'));
            exit;
        }

        View::render('auth/login', ['error' => null]);
    }

    public function login(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            View::render('auth/login', ['error' => 'Sessão expirada. Tente novamente.']);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $user = $email !== '' ? User::findByEmail($email) : null;

        if (!$user || !password_verify($password, $user['password_hash'])) {
            View::render('auth/login', ['error' => 'E-mail ou senha inválidos.']);
            return;
        }

        Auth::login($user);
        header('Location: ' . url('/'));
        exit;
    }

    public function logout(): void
    {
        if (Csrf::verify($_POST['csrf_token'] ?? null)) {
            Auth::logout();
        }
        header('Location: ' . url('/login'));
        exit;
    }

    public function account(): void
    {
        $user = User::findById(Auth::id() ?? 0);
        if (!$user) {
            Auth::logout();
            header('Location: ' . url('/login'));
            exit;
        }

        View::render('account/index', [
            'user' => $user,
            'errors' => [],
            'old' => [],
            'success' => ($_GET['updated'] ?? '') === '1',
        ]);
    }

    public function updateAccount(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }

        $userId = Auth::id() ?? 0;
        $user = User::findByIdWithPassword($userId);
        if (!$user) {
            Auth::logout();
            header('Location: ' . url('/login'));
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um e-mail válido.';
        } elseif (User::emailExistsExcept($email, $userId)) {
            $errors['email'] = 'Já existe uma conta com este e-mail.';
        }

        if ($currentPassword === '' || !password_verify($currentPassword, $user['password_hash'])) {
            $errors['current_password'] = 'Confirme sua senha atual para salvar.';
        }

        if ($newPassword !== '' && strlen($newPassword) < 8) {
            $errors['new_password'] = 'A nova senha deve ter pelo menos 8 caracteres.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'A confirmação da senha não confere.';
        }

        if (!empty($errors)) {
            View::render('account/index', [
                'user' => $user,
                'errors' => $errors,
                'old' => ['email' => $email],
                'success' => false,
            ]);
            return;
        }

        User::updateCredentials($userId, $email, $newPassword !== '' ? $newPassword : null);
        header('Location: ' . url('/account?updated=1'));
        exit;
    }
}
