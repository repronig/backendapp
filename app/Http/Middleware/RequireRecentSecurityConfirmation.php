<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRecentSecurityConfirmation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $confirmedAt = $user->last_security_confirmation_at;
        $window = (int) config('security.confirmation_window_minutes', 15);

        if (! $confirmedAt || $confirmedAt->lt(now()->subMinutes($window))) {
            return response()->json([
                'message' => 'Recent security confirmation is required for this action.',
                'data' => [
                    'confirmation_required' => true,
                    'confirmation_window_minutes' => $window,
                ],
            ], 423);
        }

        return $next($request);
    }
}
