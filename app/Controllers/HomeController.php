<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Client;
use App\Models\Dashboard;

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
        if (!Auth::isClient()) {
            $this->renderComparativo();
            return;
        }

        // client_id sempre resolvido a partir da sessão — nunca de input do usuário,
        // então o cliente final jamais consegue ver dados de outro client_id.
        $clientId = Auth::clientId();

        $data = Dashboard::forClient(
            $clientId,
            $_GET['month'] ?? null,
            $_GET['from'] ?? null,
            $_GET['to'] ?? null
        );

        View::render('dashboard/client', array_merge($data, [
            'client' => Client::find($clientId),
            'isInternal' => false,
        ]));
    }

    /** Visão interna (admin/operador): comparativo entre todos os clientes da carteira (PRD 5.6). */
    private function renderComparativo(): void
    {
        $months = Dashboard::allReferenceMonths();
        $month = $_GET['month'] ?? null;
        if ($month === null || !in_array($month, $months, true)) {
            $month = $months[0] ?? null;
        }

        View::render('dashboard/comparativo', [
            'months' => $months,
            'selectedMonth' => $month,
            'rows' => $month ? Dashboard::clientComparison($month) : [],
            'clients' => Client::all(),
        ]);
    }
}
