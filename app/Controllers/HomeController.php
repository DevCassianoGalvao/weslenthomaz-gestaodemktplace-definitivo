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
            // Dashboard interno do admin/operador (com seletor de cliente) chega na Fase 6.
            View::render('home/dashboard_placeholder', ['clientId' => null]);
            return;
        }

        // client_id sempre resolvido a partir da sessão — nunca de input do usuário,
        // então o cliente final jamais consegue ver dados de outro client_id.
        $clientId = Auth::clientId();
        $client = Client::find($clientId);

        $referenceMonths = Dashboard::referenceMonths($clientId);
        $month = $_GET['month'] ?? ($referenceMonths[0] ?? null);
        if ($month !== null && !in_array($month, $referenceMonths, true)) {
            $month = $referenceMonths[0] ?? null;
        }

        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        View::render('dashboard/client', [
            'client' => $client,
            'referenceMonths' => $referenceMonths,
            'selectedMonth' => $month,
            'from' => $from,
            'to' => $to,
            'kpis' => Dashboard::kpis($clientId, $month),
            'monthlyTotals' => Dashboard::monthlyTotals($clientId, $from, $to),
            'marketplaceTotals' => $month ? Dashboard::marketplaceTotalsForMonth($clientId, $month) : [],
            'marketplaceMatrix' => Dashboard::marketplaceMonthlyMatrix($clientId, $from, $to),
            'periods' => Dashboard::periodsWithEntries($clientId, $from, $to),
        ]);
    }
}
