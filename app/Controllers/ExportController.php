<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Client;
use App\Models\Dashboard;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exportação Excel dos dados visíveis no dashboard, refletindo os filtros ativos (PRD 5.6 / 7).
 */
class ExportController
{
    /** Cliente final exportando o próprio dashboard. */
    public function ownDashboard(): void
    {
        $this->streamForClient((int) Auth::clientId(), $_GET['month'] ?? null, $_GET['from'] ?? null, $_GET['to'] ?? null);
    }

    /** Admin/operador exportando o dashboard de um cliente específico. */
    public function forClient(string $id): void
    {
        $clientId = (int) $id;
        if (!Client::find($clientId)) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $this->streamForClient($clientId, $_GET['month'] ?? null, $_GET['from'] ?? null, $_GET['to'] ?? null);
    }

    /** Admin/operador exportando o comparativo entre todos os clientes da carteira. */
    public function comparativo(): void
    {
        $months = Dashboard::allReferenceMonths();
        $month = $_GET['month'] ?? null;
        if ($month === null || !in_array($month, $months, true)) {
            $month = $months[0] ?? null;
        }
        $rows = $month ? Dashboard::clientComparison($month) : [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comparativo');

        $sheet->setCellValue('A1', 'Competência');
        $sheet->setCellValue('B1', $month ?? '-');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $headers = ['Cliente', 'Faturamento (R$)', 'Variação vs. mês anterior', 'Pedidos', 'Ticket médio (R$)'];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:E3')->getFont()->setBold(true);

        $row = 4;
        foreach ($rows as $r) {
            $sheet->setCellValue("A{$row}", $r['client_name']);

            $sheet->setCellValue("B{$row}", $r['total_value_cents'] / 100);
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');

            if ($r['variation_pct'] !== null) {
                $sheet->setCellValue("C{$row}", $r['variation_pct'] / 100);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('0.0%');
            } else {
                $sheet->setCellValue("C{$row}", '—');
            }

            $sheet->setCellValue("D{$row}", $r['total_orders']);

            if ($r['ticket_medio_cents'] !== null) {
                $sheet->setCellValue("E{$row}", $r['ticket_medio_cents'] / 100);
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');
            } else {
                $sheet->setCellValue("E{$row}", '—');
            }

            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->stream($spreadsheet, 'comparativo-clientes-' . date('Y-m-d-His') . '.xlsx');
    }

    private function streamForClient(int $clientId, ?string $month, ?string $from, ?string $to): void
    {
        $client = Client::find($clientId);
        $data = Dashboard::forClient($clientId, $month, $from, $to);

        $spreadsheet = new Spreadsheet();
        $this->fillSummarySheet($spreadsheet->getActiveSheet(), $client, $data);

        $detailSheet = $spreadsheet->createSheet();
        $this->fillDetailSheet($detailSheet, $data['periods']);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'dashboard-' . $client['slug'] . '-' . date('Y-m-d-His') . '.xlsx';
        $this->stream($spreadsheet, $filename);
    }

    private function fillSummarySheet(Worksheet $sheet, array $client, array $data): void
    {
        $sheet->setTitle('Resumo');
        $kpis = $data['kpis'];

        $sheet->setCellValue('A1', 'Cliente');
        $sheet->setCellValue('B1', $client['name']);
        $sheet->setCellValue('A2', 'Competência selecionada');
        $sheet->setCellValue('B2', $data['selectedMonth'] ?? '-');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        $rows = [
            ['Faturamento do período', $kpis['total_value_cents'] / 100, 'currency'],
            ['Variação vs. mês anterior', $kpis['variation_pct'] !== null ? $kpis['variation_pct'] / 100 : null, 'percent'],
            ['Melhor desempenho', $kpis['best_marketplace']['name'] ?? '—', 'text'],
            ['Maior queda', $kpis['worst_marketplace']['name'] ?? '—', 'text'],
            ['Ticket médio geral', $kpis['ticket_medio_cents'] !== null ? $kpis['ticket_medio_cents'] / 100 : null, 'currency'],
        ];

        $row = 4;
        $sheet->setCellValue("A{$row}", 'Indicador');
        $sheet->setCellValue("B{$row}", 'Valor');
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($rows as [$label, $value, $type]) {
            $sheet->setCellValue("A{$row}", $label);

            if ($value === null) {
                $sheet->setCellValue("B{$row}", '—');
            } elseif ($type === 'currency') {
                $sheet->setCellValue("B{$row}", $value);
                $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');
            } elseif ($type === 'percent') {
                $sheet->setCellValue("B{$row}", $value);
                $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('0.0%');
            } else {
                $sheet->setCellValue("B{$row}", $value);
            }

            $row++;
        }

        if (!empty($kpis['marketplace_breakdown'])) {
            $row++;
            $sheet->setCellValue("A{$row}", 'Ticket médio por marketplace');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            $sheet->setCellValue("A{$row}", 'Marketplace');
            $sheet->setCellValue("B{$row}", 'Faturamento');
            $sheet->setCellValue("C{$row}", 'Pedidos');
            $sheet->setCellValue("D{$row}", 'Ticket médio');
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($kpis['marketplace_breakdown'] as $mp) {
                $sheet->setCellValue("A{$row}", $mp['name']);
                $sheet->setCellValue("B{$row}", $mp['total_value_cents'] / 100);
                $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');
                $sheet->setCellValue("C{$row}", $mp['total_orders']);
                if ($mp['ticket_medio_cents'] !== null) {
                    $sheet->setCellValue("D{$row}", $mp['ticket_medio_cents'] / 100);
                    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');
                } else {
                    $sheet->setCellValue("D{$row}", '—');
                }
                $row++;
            }
        }

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function fillDetailSheet(Worksheet $sheet, array $periods): void
    {
        $sheet->setTitle('Detalhado');

        $headers = ['Competencia', 'Periodo', 'Marketplace', 'Conta', 'Valor (R$)', 'Pedidos', 'Participacao'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $monthGroups = [];
        foreach ($periods as $period) {
            $monthGroups[$period['reference_month']][] = $period;
        }
        krsort($monthGroups);

        $row = 2;
        foreach ($monthGroups as $month => $groupPeriods) {
            $monthTotalCents = 0;

            foreach ($groupPeriods as $period) {
                $periodTotalCents = array_sum(array_column($period['entries'], 'value_cents'));
                $monthTotalCents += $periodTotalCents;

                $label = date('d/m/Y', strtotime($period['start_date'])) . ' - ' . date('d/m/Y', strtotime($period['end_date']));
                if (!empty($period['label'])) {
                    $label .= ' (' . $period['label'] . ')';
                }

                foreach ($period['entries'] as $entry) {
                    $sheet->setCellValue("A{$row}", $month);
                    $sheet->setCellValue("B{$row}", $label);
                    $sheet->setCellValue("C{$row}", $entry['marketplace_name']);
                    $sheet->setCellValue("D{$row}", $entry['account_name'] ?? '');

                    $sheet->setCellValue("E{$row}", ((int) $entry['value_cents']) / 100);
                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');

                    $sheet->setCellValue("F{$row}", (int) $entry['orders_count']);

                    $pct = $periodTotalCents > 0 ? ((int) $entry['value_cents']) / $periodTotalCents : 0;
                    $sheet->setCellValue("G{$row}", $pct);
                    $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('0.0%');

                    $row++;
                }

                $sheet->setCellValue("B{$row}", 'Total do periodo');
                $sheet->setCellValue("E{$row}", $periodTotalCents / 100);
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');
                $sheet->getStyle("B{$row}:E{$row}")->getFont()->setBold(true);
                $row++;
            }

            $sheet->setCellValue("A{$row}", "TOTAL {$month}");
            $sheet->setCellValue("E{$row}", $monthTotalCents / 100);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');
            $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
            $row += 2;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function stream(Spreadsheet $spreadsheet, string $filename): void
    {
        if (class_exists(\ZipArchive::class)) {
            $temporaryFile = tempnam(sys_get_temp_dir(), 'marketplace-export-');

            if ($temporaryFile !== false) {
                try {
                    $writer = new Xlsx($spreadsheet);
                    $writer->save($temporaryFile);

                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Cache-Control: max-age=0');
                    readfile($temporaryFile);
                    @unlink($temporaryFile);
                    exit;
                } catch (\Throwable $exception) {
                    @unlink($temporaryFile);
                }
            }
        }

        $csvFilename = preg_replace('/\.xlsx$/i', '.csv', $filename) ?: 'export.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $csvFilename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(';');
        $writer->setUseBOM(true);
        $writer->save('php://output');
        exit;
    }
}
