<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! is_string($tenantId) || ! ctype_digit($tenantId)) {
            abort(401);
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            abort(401);
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
