<?php

namespace App\Actions;

use App\Exceptions\CheckInFailed;
use App\Models\CheckIn;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class CheckOutChild
{
    public function execute(Tenant $tenant, int $checkInId): CheckIn
    {
        return DB::transaction(function () use ($tenant, $checkInId): CheckIn {
            $checkIn = CheckIn::query()
                ->forTenant($tenant)
                ->whereKey($checkInId)
                ->lockForUpdate()
                ->first();

            if ($checkIn === null) {
                throw CheckInFailed::checkInNotFound();
            }

            if ($checkIn->checked_out_at !== null) {
                throw CheckInFailed::alreadyCheckedOut();
            }

            $checkIn->checked_out_at = now();
            $checkIn->save();

            return $checkIn;
        });
    }
}
