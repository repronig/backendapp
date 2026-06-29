<?php

namespace App\Http\Middleware;

use App\Support\MemberWorkImports\MemberWorkImportSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberWorkImportEnabled
{
    public function __construct(
        protected MemberWorkImportSettings $memberWorkImportSettings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->memberWorkImportSettings->enabled()) {
            abort(404, 'Member work bulk import is not available.');
        }

        return $next($request);
    }
}
