<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreAccountRequest;
use App\Http\Requests\Accounting\UpdateAccountRequest;
use App\Models\Account;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Account Controller
 * 
 * Manages Chart of Accounts CRUD operations with tree structure.
 */
class AccountController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}
    /**
     * Display a listing of accounts with tree structure.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $search = $request->input('search');

        $query = Account::query()
            ->with('parent')
            ->searchable($search)
            ->orderBy('code');

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        // Filter active/inactive
        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->active();
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $accounts = $query->get();

        // If requesting tree structure (default)
        if (!$request->has('flat') || !$request->boolean('flat')) {
            $tree = Account::buildTree($accounts);

            return Inertia::render('accounting/accounts/index', [
                'accounts' => $tree,
                'allAccounts' => $accounts, // For dropdowns
                'filters' => [
                    'search' => $search,
                    'type' => $request->input('type'),
                    'status' => $request->input('status'),
                ],
                'accountTypes' => [
                    ['value' => 'asset', 'label' => 'Activo'],
                    ['value' => 'liability', 'label' => 'Pasivo'],
                    ['value' => 'equity', 'label' => 'Patrimonio'],
                    ['value' => 'revenue', 'label' => 'Ingresos'],
                    ['value' => 'expense', 'label' => 'Gastos'],
                ],
            ]);
        }

        // Flat list for API requests
        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Get shared data for create/edit forms.
     */
    protected function getFormData(): array
    {
        $allAccounts = Account::orderBy('code')->get();

        return [
            'allAccounts' => $allAccounts,
            'accountTypes' => [
                ['value' => 'asset', 'label' => 'Activo'],
                ['value' => 'liability', 'label' => 'Pasivo'],
                ['value' => 'equity', 'label' => 'Patrimonio'],
                ['value' => 'revenue', 'label' => 'Ingresos'],
                ['value' => 'expense', 'label' => 'Gastos'],
            ],
        ];
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): Response
    {
        return Inertia::render('accounting/accounts/create', $this->getFormData());
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(Account $account): Response
    {
        return Inertia::render('accounting/accounts/edit', array_merge(
            $this->getFormData(),
            ['account' => $account->load('parent')]
        ));
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Calculate level if parent provided
        if (isset($data['parent_id'])) {
            $parent = Account::findOrFail($data['parent_id']);
            $data['level'] = $parent->level + 1;
        } else {
            $data['level'] = 1;
        }

        $account = Account::create($data);

        $this->auditService->logCreated(AuditLog::MODULE_ACCOUNTS, $account);

        return redirect()
            ->route('accounting.accounts.index')
            ->with('success', 'Cuenta creada exitosamente');
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $account->load(['parent', 'children']),
        ]);
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $data = $request->validated();

        // Recalculate level if parent changed
        if (isset($data['parent_id']) && $data['parent_id'] !== $account->parent_id) {
            if ($data['parent_id']) {
                $parent = Account::findOrFail($data['parent_id']);
                $data['level'] = $parent->level + 1;
            } else {
                $data['level'] = 1;
            }
        }

        $oldValues = $account->toArray();

        $account->update($data);

        $this->auditService->logUpdated(AuditLog::MODULE_ACCOUNTS, $account->fresh(), $oldValues);

        return redirect()
            ->route('accounting.accounts.index')
            ->with('success', 'Cuenta actualizada exitosamente');
    }

    /**
     * Remove the specified account (soft delete).
     */
    public function destroy(Account $account): RedirectResponse
    {
        // Validate can delete
        if (!$account->canBeDeleted()) {
            return back()->with('error', 'No se puede eliminar una cuenta con cuentas hijas o movimientos contables');
        }

        $this->auditService->logDeleted(AuditLog::MODULE_ACCOUNTS, $account);

        $account->delete();

        return redirect()
            ->route('accounting.accounts.index')
            ->with('success', 'Cuenta eliminada exitosamente');
    }
}
