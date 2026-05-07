<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $isApiRequest = $request->is('api/*');

        if ($isApiRequest) {
            $request->attributes->set('is_api_request', true);

            $accept = strtolower((string) $request->header('Accept', ''));
            $wantsPdf = str_contains($accept, 'application/pdf');

            if (! $request->expectsJson() && ! $wantsPdf) {
                $request->headers->set('Accept', 'application/json');
            }
        }

        return $next($request);
    }
}
