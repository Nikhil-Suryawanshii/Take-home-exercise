<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where($query->getModel()->getTable().'.tenant_id', $tenant->getKey());
    }
}
