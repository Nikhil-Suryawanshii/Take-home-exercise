<?php

namespace App\Actions;

use App\Exceptions\CheckInFailed;
use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class CheckInChild
{
    public function execute(Tenant $tenant, int $roomId, mixed $childId): CheckIn
    {
        $room = Room::query()->forTenant($tenant)->find($roomId);

        if ($room === null) {
            throw CheckInFailed::roomNotFound();
        }

        if (! $this->hasChildId($childId)) {
            throw CheckInFailed::childIdRequired();
        }

        return DB::transaction(function () use ($tenant, $room, $childId): CheckIn {
            $child = Child::query()
                ->forTenant($tenant)
                ->whereKey($childId)
                ->lockForUpdate()
                ->first();

            if ($child === null) {
                throw CheckInFailed::childNotFound();
            }

            $alreadyOpen = CheckIn::query()
                ->forTenant($tenant)
                ->where('child_id', $child->id)
                ->whereNull('checked_out_at')
                ->lockForUpdate()
                ->first();

            if ($alreadyOpen !== null) {
                throw CheckInFailed::alreadyCheckedIn();
            }

            return CheckIn::query()->create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'child_id' => $child->id,
                'checked_in_at' => now(),
                'checked_out_at' => null,
            ]);
        });
    }

    private function hasChildId(mixed $childId): bool
    {
        if (is_int($childId)) {
            return $childId > 0;
        }

        return is_string($childId) && ctype_digit($childId) && $childId !== '0';
    }
}
