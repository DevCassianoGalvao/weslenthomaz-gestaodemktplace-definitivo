<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Client;
use App\Models\Entry;
use App\Models\Period;

class PeriodController
{
    public function index(string $clientId): void
    {
        $client = Client::find((int) $clientId);
        if (!$client) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        View::render('periods/index', [
            'client' => $client,
            'periods' => Period::allForClient((int) $clientId),
        ]);
    }

    public function create(string $clientId): void
    {
        $client = Client::find((int) $clientId);
        if (!$client) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        View::render('periods/form', [
            'mode' => 'create',
            'client' => $client,
            'period' => null,
            'marketplaces' => Client::marketplaces((int) $clientId),
            'existingEntries' => [],
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(string $clientId): void
    {
        $client = Client::find((int) $clientId);
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

        [$data, $errors] = $this->validatePeriod($_POST);
        $rows = $this->parseRows($_POST, Client::marketplaces((int) $clientId));

        if (!empty($errors)) {
            View::render('periods/form', [
                'mode' => 'create',
                'client' => $client,
                'period' => null,
                'marketplaces' => Client::marketplaces((int) $clientId),
                'existingEntries' => $rows,
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $periodId = Period::create(
            (int) $clientId,
            $data['label'],
            $data['start_date'],
            $data['end_date'],
            $data['reference_month'],
            (int) Auth::id()
        );

        Entry::saveBatch($periodId, (int) $clientId, $rows, (int) Auth::id());

        header('Location: /clients/' . (int) $clientId . '/periods');
        exit;
    }

    public function edit(string $id): void
    {
        $period = Period::find((int) $id);
        if (!$period) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $client = Client::find((int) $period['client_id']);

        View::render('periods/form', [
            'mode' => 'edit',
            'client' => $client,
            'period' => $period,
            'marketplaces' => Client::marketplaces((int) $client['id']),
            'existingEntries' => Entry::forPeriod((int) $id),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function update(string $id): void
    {
        $period = Period::find((int) $id);
        if (!$period) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Sessão expirada, volte e tente novamente.';
            return;
        }

        $client = Client::find((int) $period['client_id']);
        [$data, $errors] = $this->validatePeriod($_POST);
        $marketplaces = Client::marketplaces((int) $client['id']);
        $rows = $this->parseRows($_POST, $marketplaces);

        if (!empty($errors)) {
            View::render('periods/form', [
                'mode' => 'edit',
                'client' => $client,
                'period' => array_merge($period, $data),
                'marketplaces' => $marketplaces,
                'existingEntries' => $rows,
                'errors' => $errors,
                'old' => [],
            ]);
            return;
        }

        Period::update((int) $id, $data['label'], $data['start_date'], $data['end_date'], $data['reference_month']);
        Entry::saveBatch((int) $id, (int) $client['id'], $rows, (int) Auth::id());

        header('Location: /clients/' . (int) $client['id'] . '/periods');
        exit;
    }

    private function validatePeriod(array $input): array
    {
        $errors = [];

        $label = trim($input['label'] ?? '');
        $startDate = trim($input['start_date'] ?? '');
        $endDate = trim($input['end_date'] ?? '');
        $referenceMonth = trim($input['reference_month'] ?? '');

        if (!$this->isValidDate($startDate)) {
            $errors['start_date'] = 'Informe uma data de início válida.';
        }
        if (!$this->isValidDate($endDate)) {
            $errors['end_date'] = 'Informe uma data de fim válida.';
        }
        if (empty($errors['start_date']) && empty($errors['end_date']) && $startDate > $endDate) {
            $errors['end_date'] = 'A data de fim não pode ser anterior à data de início.';
        }

        if ($referenceMonth === '' && $this->isValidDate($startDate)) {
            $referenceMonth = Period::suggestReferenceMonth($startDate);
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $referenceMonth)) {
            $errors['reference_month'] = 'Competência inválida — use o formato AAAA-MM.';
        }

        return [
            [
                'label' => $label,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reference_month' => $referenceMonth,
            ],
            $errors,
        ];
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $date));
        return checkdate($m, $d, $y);
    }

    /**
     * @return array<int, array{value_cents:int, orders_count:int}>
     */
    private function parseRows(array $input, array $marketplaces): array
    {
        $valueCents = $input['value_cents'] ?? [];
        $ordersCount = $input['orders_count'] ?? [];
        $rows = [];

        foreach ($marketplaces as $marketplace) {
            $id = (int) $marketplace['id'];
            $rows[$id] = [
                'value_cents' => max(0, (int) ($valueCents[$id] ?? 0)),
                'orders_count' => max(0, (int) ($ordersCount[$id] ?? 0)),
            ];
        }

        return $rows;
    }
}
