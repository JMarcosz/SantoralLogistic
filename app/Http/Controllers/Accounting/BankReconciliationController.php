<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\BankReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BankReconciliationController extends Controller
{
    public function __construct(
        protected BankReconciliationService $reconciliationService,
        protected AuditService $auditService,
    ) {}

    /**
     * Display bank reconciliation dashboard.
     */
    public function index(Request $request): Response
    {
        // Get bank/cash accounts
        $bankAccounts = Account::where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $statements = BankStatement::with(['account', 'createdBy'])
            ->when($request->account_id, fn($q, $id) => $q->where('account_id', $id))
            ->orderByDesc('statement_date')
            ->paginate(15);

        return Inertia::render('accounting/bank-reconciliation/Index', [
            'bankAccounts' => $bankAccounts,
            'statements' => $statements,
            'filters' => $request->only(['account_id']),
        ]);
    }

    /**
     * Show form to create new bank statement.
     */
    public function create(): Response
    {
        $bankAccounts = Account::where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return Inertia::render('accounting/bank-reconciliation/Create', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Store a new bank statement.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'statement_date' => ['required', 'date'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'reference' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['required', 'numeric'],
            'closing_balance' => ['required', 'numeric'],
        ]);

        $statement = BankStatement::create([
            ...$validated,
            'status' => BankStatement::STATUS_DRAFT,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('accounting.bank-reconciliation.show', $statement)
            ->with('success', 'Estado de cuenta creado. Ahora puede agregar las transacciones.');
    }

    /**
     * Show reconciliation interface for a statement.
     */
    public function show(BankStatement $bankStatement): Response
    {
        $bankStatement->load([
            'account',
            'lines' => fn($q) => $q->orderBy('transaction_date'),
            'lines.journalEntryLine.journalEntry',
            'lines.payment',
            'lines.reconciledBy',
        ]);

        // Get unreconciled GL lines for this account
        $unreconciledGlLines = JournalEntryLine::with(['journalEntry'])
            ->where('account_id', $bankStatement->account_id)
            ->where('is_reconciled', false)
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'posted'))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return Inertia::render('accounting/bank-reconciliation/Show', [
            'statement' => $bankStatement,
            'unreconciledGlLines' => $unreconciledGlLines,
        ]);
    }

    /**
     * Add a line to the statement (manual entry).
     */
    public function addLine(Request $request, BankStatement $bankStatement): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric'],
            'transaction_type' => ['nullable', 'string', 'max:50'],
        ]);

        BankStatementLine::create([
            'bank_statement_id' => $bankStatement->id,
            ...$validated,
        ]);

        $bankStatement->recalculateTotals();

        return back()->with('success', 'Línea agregada.');
    }

    /**
     * Delete a statement line.
     */
    public function deleteLine(BankStatement $bankStatement, BankStatementLine $line): RedirectResponse
    {
        if ($line->is_reconciled) {
            return back()->with('error', 'No se puede eliminar una línea conciliada.');
        }

        $line->delete();
        $bankStatement->recalculateTotals();

        return back()->with('success', 'Línea eliminada.');
    }

    /**
     * Match a statement line to a GL line.
     */
    public function matchLine(Request $request, BankStatementLine $line): RedirectResponse
    {
        $validated = $request->validate([
            'journal_entry_line_id' => ['nullable', 'exists:journal_entry_lines,id'],
            'payment_id' => ['nullable', 'exists:payments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['journal_entry_line_id']) {
            $glLine = JournalEntryLine::findOrFail($validated['journal_entry_line_id']);
            $this->reconciliationService->reconcileWithJournalLine($line, $glLine, $validated['notes'] ?? null);
        } elseif ($validated['payment_id']) {
            $payment = Payment::findOrFail($validated['payment_id']);
            $this->reconciliationService->reconcileWithPayment($line, $payment, $validated['notes'] ?? null);
        }

        $this->auditService->log(
            AuditLog::ACTION_CREATED,
            AuditLog::MODULE_BANK_RECONCILIATION,
            $line,
            "Línea bancaria conciliada: {$line->description}"
        );

        return back()->with('success', 'Línea conciliada.');
    }

    /**
     * Unmatch a statement line.
     */
    public function unmatchLine(BankStatementLine $line): RedirectResponse
    {
        $this->auditService->log(
            AuditLog::ACTION_DELETED,
            AuditLog::MODULE_BANK_RECONCILIATION,
            $line,
            "Conciliación removida: {$line->description}"
        );

        $this->reconciliationService->unreconcile($line);
        return back()->with('success', 'Conciliación removida.');
    }

    /**
     * Find potential matches for a line (AJAX).
     */
    public function findMatches(BankStatementLine $line)
    {
        $matches = $this->reconciliationService->findPotentialMatches($line);
        return response()->json($matches);
    }

    /**
     * Import lines from CSV.
     */
    public function importCsv(Request $request, BankStatement $bankStatement): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $rows = [];

        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            $headers = fgetcsv($handle);
            $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $data);
                $rows[] = $row;
            }
            fclose($handle);
        }

        $imported = $this->reconciliationService->importFromCsv($bankStatement, $rows);

        return back()->with('success', "Se importaron {$imported} transacciones.");
    }

    /**
     * Mark statement as completed.
     */
    public function complete(BankStatement $bankStatement): RedirectResponse
    {
        if (!$bankStatement->isFullyReconciled()) {
            return back()->with('error', 'El estado de cuenta no está completamente conciliado.');
        }

        $bankStatement->markCompleted(Auth::id());

        $this->auditService->log(
            AuditLog::ACTION_UPDATED,
            AuditLog::MODULE_BANK_RECONCILIATION,
            $bankStatement->fresh(),
            "Estado de cuenta completado: {$bankStatement->reference}"
        );

        return back()->with('success', 'Estado de cuenta marcado como completado.');
    }

    /**
     * Unreconciled items report.
     */
    public function unreconciledReport(Request $request): Response
    {
        $accountId = $request->get('account_id');
        $account = $accountId ? Account::find($accountId) : null;

        $report = [];
        if ($account) {
            $report = $this->reconciliationService->getUnreconciledItems(
                $account,
                $request->get('from_date'),
                $request->get('to_date')
            );
        }

        $bankAccounts = Account::where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return Inertia::render('accounting/bank-reconciliation/UnreconciledReport', [
            'bankAccounts' => $bankAccounts,
            'selectedAccount' => $account,
            'report' => $report,
            'filters' => $request->only(['account_id', 'from_date', 'to_date']),
        ]);
    }
}
