<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Accounting Dashboard Controller
 * 
 * Main entry point for accounting module.
 */
class AccountingController extends Controller
{
    /**
     * Display accounting dashboard.
     */
    public function index(Request $request): Response
    {
        // Authorization handled by route middleware: can:accounting.view

        return Inertia::render('accounting/index', [
            'can' => [
                'manage' => $request->user()->can('accounting.manage'),
                'post' => $request->user()->can('accounting.post'),
                'closePeriod' => $request->user()->can('accounting.close_period'),
            ],
        ]);
    }
}
