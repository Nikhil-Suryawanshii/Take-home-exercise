<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(CurrentTenant $current): JsonResponse
    {
        $rooms = Room::query()
            ->forTenant($current->get())
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rooms);
    }

    public function store(Request $request, CurrentTenant $current): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $room = Room::query()->create([
            'tenant_id' => $current->get()->id,
            'name' => $data['name'],
        ]);

        return response()->json($room->only(['id', 'name']), 201);
    }

    public function update(Request $request, CurrentTenant $current, int $room): JsonResponse
    {
        $record = Room::query()->forTenant($current->get())->find($room);

        if ($record === null) {
            return response()->json(['message' => 'Room not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $record->update($data);

        return response()->json($record->only(['id', 'name']));
    }

    public function destroy(CurrentTenant $current, int $room): JsonResponse
    {
        $record = Room::query()->forTenant($current->get())->find($room);

        if ($record === null) {
            return response()->json(['message' => 'Room not found.'], 404);
        }

        if ($record->checkIns()->exists()) {
            return response()->json(['message' => 'This room still has check-ins.'], 409);
        }

        $record->delete();

        return response()->json(null, 204);
    }
}
