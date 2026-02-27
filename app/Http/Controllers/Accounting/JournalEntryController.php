<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\JournalEntryStatus;
use App\Exceptions\Accounting\JournalEntryNotBalancedException;
use App\Exceptions\Accounting\PeriodClosedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreJournalEntryRequest;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Services\AuditService;
use App\Services\JournalPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function __construct(
        protected JournalPostingService $postingService,
        protected AuditService $auditService
    ) {}

    /**
     * Display a listing of journal entries.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $query = JournalEntry::with(['createdBy:id,name', 'postedBy:id,name'])
            ->withCount('lines');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Filter by source type
        if ($request->filled('source_type')) {
            if ($request->source_type === 'manual') {
                $query->manual();
            } else {
                $query->bySource($request->source_type);
            }
        }

        // Search by entry number or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Order by date and entry number
        $query->orderBy('date', 'desc')
            ->orderBy('entry_number', 'desc');

        $entries = $query->paginate(20)->withQueryString();

        // Get status options for filter
        $statuses = collect(JournalEntryStatus::cases())->map(fn($s) => [
            'value' => $s->value,
            'label' => $s->label(),
        ]);

        return Inertia::render('accounting/journal-entries/index', [
            'entries' => $entries,
            'statuses' => $statuses,
            'filters' => [
                'status' => $request->status,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'source_type' => $request->source_type,
                'search' => $request->search,
            ],
            'can' => [
                'create' => $request->user()->can('create', JournalEntry::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new journal entry.
     */
    public function create(): Response
    {
        $this->authorize('create', JournalEntry::class);

        $accounts = Account::active()
            ->postable()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'normal_balance', 'currency_code']);

        $currencies = Currency::orderBy('code')->get(['id', 'code', 'symbol', 'name']);

        $defaultCurrency = Currency::where('is_default', true)->first();

        return Inertia::render('accounting/journal-entries/create', [
            'accounts' => $accounts,
            'currencies' => $currencies,
            'defaultCurrencyCode' => $defaultCurrency?->code ?? 'DOP',
        ]);
    }

    /**
     * Store a newly created journal entry.
     */
    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $entry = $this->postingService->create(
            $request->only(['date', 'description']),
            $request->input('lines')
        );

        $this->auditService->logCreated(AuditLog::MODULE_JOURNAL_ENTRIES, $entry);

        return redirect()
            ->route('accounting.journal-entries.show', $entry)
            ->with('success', "Asiento {$entry->entry_number} creado exitosamente.");
    }

    /**
     * Display the specified journal entry.
     */
    public function show(JournalEntry $journalEntry): Response
    {
        $this->authorize('view', $journalEntry);

        $journalEntry->load([
            'lines.account:id,code,name,type,normal_balance',
            'createdBy:id,name',
            'postedBy:id,name',
            'reversedBy:id,name',
            'reversalOf:id,entry_number',
            'reversalEntry:id,entry_number,reversal_of_entry_id',
        ]);

        $user = request()->user();

        return Inertia::render('accounting/journal-entries/show', [
            'entry' => $journalEntry,
            'can' => [
                'update' => $user->can('update', $journalEntry),
                'delete' => $user->can('delete', $journalEntry),
                'post' => $user->can('post', $journalEntry),
                'reverse' => $user->can('reverse', $journalEntry),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified journal entry.
     */
    public function edit(JournalEntry $journalEntry): Response
    {
        $this->authorize('update', $journalEntry);

        $journalEntry->load(['lines.account:id,code,name']);

        $accounts = Account::active()
            ->postable()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'normal_balance', 'currency_code']);

        $currencies = Currency::orderBy('code')->get(['id', 'code', 'symbol', 'name']);

        return Inertia::render('accounting/journal-entries/edit', [
            'entry' => $journalEntry,
            'accounts' => $accounts,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Update the specified journal entry.
     */
    public function update(StoreJournalEntryRequest $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('update', $journalEntry);

        $oldValues = $journalEntry->toArray();

        $this->postingService->update(
            $journalEntry,
            $request->only(['date', 'description']),
            $request->input('lines')
        );

        $this->auditService->logUpdated(AuditLog::MODULE_JOURNAL_ENTRIES, $journalEntry->fresh(), $oldValues);

        return redirect()
            ->route('accounting.journal-entries.show', $journalEntry)
            ->with('success', "Asiento {$journalEntry->entry_number} actualizado exitosamente.");
    }

    /**
     * Remove the specified journal entry.
     */
    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('delete', $journalEntry);

        $entryNumber = $journalEntry->entry_number;

        $this->auditService->logDeleted(AuditLog::MODULE_JOURNAL_ENTRIES, $journalEntry);

        $journalEntry->lines()->delete();
        $journalEntry->delete();

        return redirect()
            ->route('accounting.journal-entries.index')
            ->with('success', "Asiento {$entryNumber} eliminado exitosamente.");
    }

    /**
     * Post the journal entry.
     */
    public function post(JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('post', $journalEntry);

        try {
            $this->postingService->post($journalEntry);

            $this->auditService->logPosted($journalEntry->fresh());

            return redirect()
                ->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', "Asiento {$journalEntry->entry_number} contabilizado exitosamente.");
        } catch (JournalEntryNotBalancedException $e) {
            return back()->with('error', $e->getMessage());
        } catch (PeriodClosedException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reverse the journal entry.
     */
    public function reverse(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('reverse', $journalEntry);

        try {
            $description = $request->input('description');
            $reversalEntry = $this->postingService->reverse($journalEntry, $description);

            $this->auditService->logReversed($journalEntry->fresh(), $reversalEntry);

            return redirect()
                ->route('accounting.journal-entries.show', $reversalEntry)
                ->with('success', "Asiento {$journalEntry->entry_number} reversado. Nuevo asiento: {$reversalEntry->entry_number}");
        } catch (PeriodClosedException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
