<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RetiredWorkflow
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('carverse.legacy_workflows'), 404);

        return $next($request);
    }
}
