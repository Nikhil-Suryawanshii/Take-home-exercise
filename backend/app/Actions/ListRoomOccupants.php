<?php

namespace App\Actions;

use App\Exceptions\CheckInFailed;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\Tenant;

class ListRoomOccupants
{
    /**
     * @return list<array{check_in_id: int, child_id: int, name: string}>
     */
    public function execute(Tenant $tenant, int $roomId): array
    {
        $room = Room::query()->forTenant($tenant)->find($roomId);

        if ($room === null) {
            throw CheckInFailed::roomNotFound();
        }

        return CheckIn::query()
            ->forTenant($tenant)
            ->where('room_id', $room->id)
            ->whereNull('checked_out_at')
            ->with('child')
            ->orderBy('id')
            ->get()
            ->map(fn (CheckIn $checkIn) => [
                'check_in_id' => $checkIn->id,
                'child_id' => $checkIn->child_id,
                'name' => $checkIn->child->name,
            ])
            ->all();
    }
}
