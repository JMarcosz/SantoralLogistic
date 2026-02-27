<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ChangePasswordController extends Controller
{
    /**
     * Show the change password form.
     */
    public function show(): Response
    {
        return Inertia::render('auth/change-password');
    }

    /**
     * Update the user's password.
     */
    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Tu contraseña ha sido actualizada exitosamente. ¡Bienvenido!');
    }
}
