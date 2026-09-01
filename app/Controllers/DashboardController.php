<?php

namespace App\Controllers;

use App\Core\View;
use App\Models\Client;
use App\Models\Dashboard;

class DashboardController
{
    /** Drill-down do admin/operador num cliente específico — mesma view do cliente final, com seletor e abas. */
    public function client(string $id): void
    {
        $clientId = (int) $id;
        $client = Client::find($clientId);
        if (!$client) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $data = Dashboard::forClient(
            $clientId,
            $_GET['month'] ?? null,
            $_GET['from'] ?? null,
            $_GET['to'] ?? null
        );

        View::render('dashboard/client', array_merge($data, [
            'client' => $client,
            'isInternal' => true,
            'allClients' => Client::all(),
        ]));
    }
}
