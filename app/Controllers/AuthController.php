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
}
