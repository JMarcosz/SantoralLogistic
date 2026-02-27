<?php

namespace App\Http\Controllers;

use App\Models\DgiiExportLog;
use App\Services\DgiiExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Controller for DGII (Dirección General de Impuestos Internos) export reports.
 */
class DgiiExportController extends Controller
{
    public function __construct(
        protected DgiiExportService $dgiiExportService
    ) {}

    /**
     * Export 607 report (Sales/Income).
     * 
     * Query params:
     * - period: YYYY-MM (required)
     * 
     * @param Request $request
     * @return Response
     */
    public function export607(Request $request): Response
    {
        // Authorization: user must have invoices.view_any permission
        if (!$request->user()->can('invoices.view_any')) {
            abort(403, 'No tiene permiso para exportar reportes DGII');
        }

        // Accept either 'period' (YYYY-MM) or 'period_start' + 'period_end' (YYYY-MM-DD)
        if ($request->has('period_start') && $request->has('period_end')) {
            // Format: period_start=YYYY-MM-DD&period_end=YYYY-MM-DD
            $request->validate([
                'period_start' => ['required', 'date'],
                'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            ]);

            $periodStart = Carbon::parse($request->period_start)->startOfDay();
            $periodEnd = Carbon::parse($request->period_end)->endOfDay();

            $filename = "607_{$periodStart->format('Ymd')}_{$periodEnd->format('Ymd')}.txt";
        } else {
            // Format: period=YYYY-MM
            $request->validate([
                'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            ]);

            [$year, $month] = explode('-', $request->period);
            $periodStart = Carbon::create($year, $month, 1)->startOfDay();
            $periodEnd = $periodStart->copy()->endOfMonth()->endOfDay();

            $filename = "607_{$year}{$month}.txt";
        }

        try {
            $content = $this->dgiiExportService->generate607($periodStart, $periodEnd);

            // Log export
            DgiiExportLog::create([
                'report_type' => '607',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'record_count' => substr_count($content, "\n") + ($content ? 1 : 0),
                'user_id' => $request->user()->id,
                'filename' => $filename,
            ]);

            return response($content)
                ->header('Content-Type', 'text/plain; charset=utf-8')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('Error generating 607 report', [
                'params' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Error generando reporte 607. Por favor contacte al administrador.', 500)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }
    }

    /**
     * Export 608 report (Cancelled Invoices).
     * 
     * Query params:
     * - period: YYYY-MM (required)
     * 
     * @param Request $request
     * @return Response
     */
    public function export608(Request $request): Response
    {
        // Authorization: user must have invoices.view_any permission
        if (!$request->user()->can('invoices.view_any')) {
            abort(403, 'No tiene permiso para exportar reportes DGII');
        }

        // Accept either 'period' (YYYY-MM) or 'period_start' + 'period_end' (YYYY-MM-DD)
        if ($request->has('period_start') && $request->has('period_end')) {
            // Format: period_start=YYYY-MM-DD&period_end=YYYY-MM-DD
            $request->validate([
                'period_start' => ['required', 'date'],
                'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            ]);

            $periodStart = Carbon::parse($request->period_start)->startOfDay();
            $periodEnd = Carbon::parse($request->period_end)->endOfDay();

            $filename = "608_{$periodStart->format('Ymd')}_{$periodEnd->format('Ymd')}.txt";
        } else {
            // Format: period=YYYY-MM
            $request->validate([
                'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            ]);

            [$year, $month] = explode('-', $request->period);
            $periodStart = Carbon::create($year, $month, 1)->startOfDay();
            $periodEnd = $periodStart->copy()->endOfMonth()->endOfDay();

            $filename = "608_{$year}{$month}.txt";
        }

        try {
            $content = $this->dgiiExportService->generate608($periodStart, $periodEnd);

            // Log export
            DgiiExportLog::create([
                'report_type' => '608',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'record_count' => substr_count($content, "\n") + ($content ? 1 : 0),
                'user_id' => $request->user()->id,
                'filename' => $filename,
            ]);

            return response($content)
                ->header('Content-Type', 'text/plain; charset=utf-8')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('Error generating 608 report', [
                'params' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Error generando reporte 608. Por favor contacte al administrador.', 500)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }
    }

    /**
     * Get statistics for a period.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        // Authorization
        if (!$request->user()->can('invoices.view_any')) {
            abort(403, 'No tiene permiso para ver estadísticas DGII');
        }

        $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        [$year, $month] = explode('-', $request->period);
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth()->endOfDay();

        $stats = $this->dgiiExportService->getStatistics($periodStart, $periodEnd);

        return response()->json($stats);
    }

    /**
     * Get export history.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        // Authorization
        if (!$request->user()->can('invoices.view_any')) {
            abort(403, 'No tiene permiso para ver historial de exportaciones');
        }

        $history = DgiiExportLog::with('user')
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'report_type' => $log->report_type,
                    'period_display' => Carbon::parse($log->period_start)->translatedFormat('F Y'),
                    'period_start' => $log->period_start->format('Y-m-d'),
                    'period_end' => $log->period_end->format('Y-m-d'),
                    'record_count' => $log->record_count,
                    'filename' => $log->filename,
                    'user_name' => $log->user->name,
                    'created_at' => $log->created_at->diffForHumans(),
                    'created_at_full' => $log->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json($history);
    }
}
