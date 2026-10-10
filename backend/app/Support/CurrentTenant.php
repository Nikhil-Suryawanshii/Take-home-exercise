<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Http\Request;

class CurrentTenant
{
    public function __construct(private readonly Request $request) {}

    public function get(): Tenant
    {
        $tenant = $this->request->attributes->get('tenant');

        if (! $tenant instanceof Tenant) {
            abort(401);
        }

        return $tenant;
    }
}
