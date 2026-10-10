<?php

namespace App\Http\Controllers;

use App\Actions\CheckInChild;
use App\Actions\CheckOutChild;
use App\Exceptions\CheckInFailed;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function store(Request $request, CurrentTenant $current, CheckInChild $checkInChild, int $room): JsonResponse
    {
        try {
            $checkIn = $checkInChild->execute($current->get(), $room, $request->input('child_id'));
        } catch (CheckInFailed $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status);
        }

        return response()->json([
            'id' => $checkIn->id,
            'room_id' => $checkIn->room_id,
            'child_id' => $checkIn->child_id,
            'checked_in_at' => $checkIn->checked_in_at,
        ], 201);
    }

    public function checkOut(CurrentTenant $current, CheckOutChild $checkOutChild, int $id): JsonResponse
    {
        try {
            $checkIn = $checkOutChild->execute($current->get(), $id);
        } catch (CheckInFailed $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status);
        }

        return response()->json([
            'id' => $checkIn->id,
            'room_id' => $checkIn->room_id,
            'child_id' => $checkIn->child_id,
            'checked_in_at' => $checkIn->checked_in_at,
            'checked_out_at' => $checkIn->checked_out_at,
        ]);
    }
}
