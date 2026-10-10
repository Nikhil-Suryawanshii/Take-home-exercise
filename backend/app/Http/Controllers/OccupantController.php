<?php

namespace App\Http\Controllers;

use App\Actions\ListRoomOccupants;
use App\Exceptions\CheckInFailed;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;

class OccupantController extends Controller
{
    public function index(CurrentTenant $current, ListRoomOccupants $listRoomOccupants, int $room): JsonResponse
    {
        try {
            $occupants = $listRoomOccupants->execute($current->get(), $room);
        } catch (CheckInFailed $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status);
        }

        return response()->json($occupants);
    }
}
