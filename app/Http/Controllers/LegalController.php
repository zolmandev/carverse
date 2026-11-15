<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class LegalController extends Controller
{
    public function show(string $page)
    {
        abort_unless(in_array($page, ['terms', 'privacy', 'community-guidelines'], true), 404);

        return Inertia::render('carverse/legal', [
            'page' => $page,
            'updatedAt' => '16 November 2026',
        ]);
    }
}
