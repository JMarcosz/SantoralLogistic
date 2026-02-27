<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::withCount('users', 'permissions')
            ->latest()
            ->paginate(15);

        return Inertia::render('roles/index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): Response
    {
        $this->authorize('create', Role::class);

        $permissions = $this->getGroupedPermissions();

        return Inertia::render('roles/create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol creado exitosamente.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): Response
    {
        $this->authorize('view', $role);

        $role->load('permissions');

        return Inertia::render('roles/show', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        $role->load('permissions');

        $permissions = $this->getGroupedPermissions();

        return Inertia::render('roles/edit', [
            'role' => $role,
            'permissions' => $permissions,
            'currentPermissions' => $role->permissions->pluck('name')->toArray(),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => $request->name,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol actualizado exitosamente.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol eliminado exitosamente.');
    }

    /**
     * Get permissions grouped by module.
     */
    protected function getGroupedPermissions(): array
    {
        $permissions = Permission::all();

        return $permissions->groupBy(function ($permission) {
            return Str::before($permission->name, '.');
        })->map(function ($modulePermissions, $module) {
            return [
                'module' => $module,
                'label' => ucfirst(str_replace('_', ' ', $module)),
                'permissions' => $modulePermissions->map(fn($p) => [
                    'name' => $p->name,
                    'label' => Str::title(str_replace('_', ' ', Str::after($p->name, '.'))),
                ])->toArray(),
            ];
        })->values()->toArray();
    }
}
