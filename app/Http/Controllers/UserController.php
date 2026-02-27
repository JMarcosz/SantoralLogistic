<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->latest()
            ->paginate(15);

        return Inertia::render('users/index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        $roles = Role::all(['name', 'id'])
            ->map(fn($role) => [
                'value' => $role->name,
                'label' => ucfirst(str_replace('_', ' ', $role->name)),
            ]);

        return Inertia::render('users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        // Generate temporary password
        $temporaryPassword = \Illuminate\Support\Str::password(length: 10);

        // Create user with hashed password
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true, // Force password change on first login
        ]);

        // Assign role
        $user->assignRole($request->role);

        // Send welcome email (queued for performance)
        \Illuminate\Support\Facades\Mail::to($user->email)->queue(
            new \App\Mail\WelcomeNewUser($user, $temporaryPassword)
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado exitosamente. Las credenciales han sido enviadas por correo.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load('roles');

        return Inertia::render('users/show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles');

        $roles = Role::all(['name', 'id'])
            ->map(fn($role) => [
                'value' => $role->name,
                'label' => ucfirst(str_replace('_', ' ', $role->name)),
            ]);

        return Inertia::render('users/edit', [
            'user' => $user,
            'roles' => $roles,
            'currentRole' => $user->roles->first()?->name,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Sync role
        $user->syncRoles([$request->role]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
