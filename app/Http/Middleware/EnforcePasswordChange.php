<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordChange
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs('password.*', 'logout', 'local-assets.*')) {
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
