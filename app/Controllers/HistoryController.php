<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Format;
use App\Core\View;
use App\Models\Client;
use App\Models\EntryHistory;
use App\Models\Marketplace;

class HistoryController
{
    public function index(): void
    {
        $filters = $this->readFilters();

        View::render('history/index', [
            'entries' => EntryHistory::list($filters),
            'clients' => Client::all(),
            'marketplaces' => Marketplace::all(),
            'filters' => $filters,
        ]);
    }

    public function exportCsv(): void
    {
        $filters = $this->readFilters();
        $rows = EntryHistory::list($filters, 100000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="historico-lancamentos-' . date('Y-m-d-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF"); // BOM para o Excel abrir acentos corretamente
        fputcsv($out, ['Data/hora', 'Cliente', 'Marketplace', 'Conta', 'Competência', 'Ação', 'Valor anterior', 'Valor novo', 'Pedidos anterior', 'Pedidos novo', 'Alterado por'], ';');

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['changed_at'],
                $row['client_name'],
                $row['marketplace_name'],
                $row['account_name'] ?? '',
                $row['reference_month'],
                $row['action'],
                $row['old_value_cents'] !== null ? Format::centsToBrl((int) $row['old_value_cents']) : '',
                Format::centsToBrl((int) $row['new_value_cents']),
                $row['old_orders_count'] ?? '',
                $row['new_orders_count'],
                $row['changed_by_name'],
            ], ';');
        }

        fclose($out);
        exit;
    }

    public function clearClient(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $client = $clientId > 0 ? Client::find($clientId) : null;
        $confirmation = trim($_POST['confirmation'] ?? '');

        if (!$client || $confirmation !== $client['name']) {
            $filters = $this->readFilters();
            View::render('history/index', [
                'entries' => EntryHistory::list($filters),
                'clients' => Client::all(),
                'marketplaces' => Marketplace::all(),
                'filters' => $filters,
                'clearError' => 'Nome do cliente não confere. Nada foi apagado.',
            ]);
            return;
        }

        EntryHistory::clearForClient($clientId);

        header('Location: ' . url('/history?cleared=client'));
        exit;
    }

    public function clearAll(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }

        $confirmation = trim($_POST['confirmation'] ?? '');

        if ($confirmation !== 'LIMPAR HISTORICO') {
            $filters = $this->readFilters();
            View::render('history/index', [
                'entries' => EntryHistory::list($filters),
                'clients' => Client::all(),
                'marketplaces' => Marketplace::all(),
                'filters' => $filters,
                'clearError' => 'Frase de confirmação incorreta. Nada foi apagado.',
            ]);
            return;
        }

        EntryHistory::clearAll();

        header('Location: ' . url('/history?cleared=all'));
        exit;
    }

    private function readFilters(): array
    {
        return array_filter([
            'client_id' => $_GET['client_id'] ?? null,
            'marketplace_id' => $_GET['marketplace_id'] ?? null,
            'reference_month' => $_GET['reference_month'] ?? null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ]);
    }
}
