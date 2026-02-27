<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\FinancialStatementsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialReportsController extends Controller
{
    public function __construct(
        protected FinancialStatementsService $financialStatementsService,
    ) {}

    /**
     * Display reports dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('accounting/reports/index', [
            'availableReports' => [
                [
                    'name' => 'Balance General',
                    'description' => 'Estado de situación financiera',
                    'route' => 'accounting.reports.balance-sheet',
                    'icon' => 'scale',
                ],
                [
                    'name' => 'Estado de Resultados',
                    'description' => 'Ingresos y gastos del período',
                    'route' => 'accounting.reports.income-statement',
                    'icon' => 'trending-up',
                ],
            ],
        ]);
    }

    /**
     * Display Balance Sheet report.
     */
    public function balanceSheet(Request $request): Response
    {
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $includeZeroBalances = $request->boolean('include_zero_balances', false);

        $report = $this->financialStatementsService->getBalanceSheet(
            $asOfDate,
            $includeZeroBalances
        );

        return Inertia::render('accounting/reports/balance-sheet', [
            'report' => $report,
            'filters' => [
                'as_of_date' => $asOfDate,
                'include_zero_balances' => $includeZeroBalances,
            ],
        ]);
    }

    /**
     * Display Income Statement report.
     */
    public function incomeStatement(Request $request): Response
    {
        $period = $request->get('period', now()->format('Y-m'));
        $ytd = $request->boolean('ytd', false);

        $report = $this->financialStatementsService->getIncomeStatement($period, $ytd);

        // Generate period options (last 24 months)
        $periodOptions = [];
        $current = Carbon::now()->startOfMonth();
        for ($i = 0; $i < 24; $i++) {
            $periodOptions[] = [
                'value' => $current->format('Y-m'),
                'label' => $current->translatedFormat('F Y'),
            ];
            $current->subMonth();
        }

        return Inertia::render('accounting/reports/income-statement', [
            'report' => $report,
            'periodOptions' => $periodOptions,
            'filters' => [
                'period' => $period,
                'ytd' => $ytd,
            ],
        ]);
    }

    /**
     * Export Balance Sheet to CSV/PDF.
     */
    public function exportBalanceSheet(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $format = $request->get('format', 'csv');

        $report = $this->financialStatementsService->getBalanceSheet($asOfDate, true);

        if ($format === 'csv') {
            return $this->generateBalanceSheetCsv($report);
        }

        // PDF would require a package like DomPDF or Snappy
        return back()->with('error', 'Formato no soportado aún.');
    }

    /**
     * Export Income Statement to CSV/PDF.
     */
    public function exportIncomeStatement(Request $request)
    {
        $period = $request->get('period', now()->format('Y-m'));
        $ytd = $request->boolean('ytd', false);
        $format = $request->get('format', 'csv');

        $report = $this->financialStatementsService->getIncomeStatement($period, $ytd);

        if ($format === 'csv') {
            return $this->generateIncomeStatementCsv($report);
        }

        return back()->with('error', 'Formato no soportado aún.');
    }

    /**
     * Compare income statements between periods.
     */
    public function compareIncomeStatements(Request $request): Response
    {
        $period1 = $request->get('period_1', now()->format('Y-m'));
        $period2 = $request->get('period_2', now()->subMonth()->format('Y-m'));

        $comparison = $this->financialStatementsService->compareIncomeStatements($period1, $period2);

        return Inertia::render('accounting/reports/IncomeStatementComparison', [
            'comparison' => $comparison,
            'filters' => [
                'period_1' => $period1,
                'period_2' => $period2,
            ],
        ]);
    }

    /**
     * Generate CSV for Balance Sheet.
     */
    protected function generateBalanceSheetCsv(array $report)
    {
        $filename = 'balance_sheet_' . $report['as_of_date'] . '.csv';

        $callback = function () use ($report) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['Balance General - ' . $report['as_of_date']]);
            fputcsv($file, []);

            // Assets
            fputcsv($file, ['ACTIVOS']);
            fputcsv($file, ['Código', 'Cuenta', 'Balance']);
            foreach ($report['assets']['accounts'] as $account) {
                fputcsv($file, [$account['code'], $account['name'], $account['balance']]);
            }
            fputcsv($file, ['', 'Total Activos', $report['assets']['total']]);
            fputcsv($file, []);

            // Liabilities
            fputcsv($file, ['PASIVOS']);
            fputcsv($file, ['Código', 'Cuenta', 'Balance']);
            foreach ($report['liabilities']['accounts'] as $account) {
                fputcsv($file, [$account['code'], $account['name'], $account['balance']]);
            }
            fputcsv($file, ['', 'Total Pasivos', $report['liabilities']['total']]);
            fputcsv($file, []);

            // Equity
            fputcsv($file, ['PATRIMONIO']);
            fputcsv($file, ['Código', 'Cuenta', 'Balance']);
            foreach ($report['equity']['accounts'] as $account) {
                fputcsv($file, [$account['code'], $account['name'], $account['balance']]);
            }
            fputcsv($file, ['', 'Resultados Acumulados', $report['equity']['retained_earnings']]);
            fputcsv($file, ['', 'Total Patrimonio', $report['equity']['total']]);
            fputcsv($file, []);

            fputcsv($file, ['', 'Total Pasivos + Patrimonio', $report['total_liabilities_equity']]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate CSV for Income Statement.
     */
    protected function generateIncomeStatementCsv(array $report)
    {
        $filename = 'income_statement_' . $report['period'] . '.csv';

        $callback = function () use ($report) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $title = 'Estado de Resultados - ' . $report['period'];
            if ($report['is_ytd']) {
                $title .= ' (Acumulado YTD)';
            }
            fputcsv($file, [$title]);
            fputcsv($file, ['Período: ' . $report['period_start'] . ' a ' . $report['period_end']]);
            fputcsv($file, []);

            // Revenue
            fputcsv($file, ['INGRESOS']);
            fputcsv($file, ['Código', 'Cuenta', 'Monto']);
            foreach ($report['revenue']['accounts'] as $account) {
                fputcsv($file, [$account['code'], $account['name'], $account['balance']]);
            }
            fputcsv($file, ['', 'Total Ingresos', $report['revenue']['total']]);
            fputcsv($file, []);

            // Expenses
            fputcsv($file, ['GASTOS']);
            fputcsv($file, ['Código', 'Cuenta', 'Monto']);
            foreach ($report['expenses']['accounts'] as $account) {
                fputcsv($file, [$account['code'], $account['name'], $account['balance']]);
            }
            fputcsv($file, ['', 'Total Gastos', $report['expenses']['total']]);
            fputcsv($file, []);

            fputcsv($file, ['', 'UTILIDAD NETA', $report['net_income']]);
            fputcsv($file, ['', 'Margen Bruto (%)', number_format($report['gross_margin'], 2)]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
