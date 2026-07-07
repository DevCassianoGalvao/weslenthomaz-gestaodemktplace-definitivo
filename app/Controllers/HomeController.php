<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class HomeController
{
    /**
     * Roteamento pós-login: admin/operator caem na lista de clientes,
     * cliente final cai direto no próprio dashboard (PRD 5.1).
     */
    public function index(): void
    {
        if (Auth::isClient()) {
            header('Location: /dashboard');
        } else {
            header('Location: /clients');
        }
        exit;
    }

    public function dashboard(): void
    {
        // Fase 1: placeholder. client_id sempre resolvido a partir da sessão
        // (nunca de input do usuário) — cliente só pode ver o próprio.
        $clientId = Auth::isClient() ? Auth::clientId() : null;

        View::render('home/dashboard_placeholder', [
            'clientId' => $clientId,
        ]);
    }
}
