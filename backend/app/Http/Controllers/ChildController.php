<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index(CurrentTenant $current): JsonResponse
    {
        $children = Child::query()
            ->forTenant($current->get())
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($children);
    }

    public function store(Request $request, CurrentTenant $current): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $child = Child::query()->create([
            'tenant_id' => $current->get()->id,
            'name' => $data['name'],
        ]);

        return response()->json($child->only(['id', 'name']), 201);
    }

    public function update(Request $request, CurrentTenant $current, int $child): JsonResponse
    {
        $record = Child::query()->forTenant($current->get())->find($child);

        if ($record === null) {
            return response()->json(['message' => 'Child not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $record->update($data);

        return response()->json($record->only(['id', 'name']));
    }

    public function destroy(CurrentTenant $current, int $child): JsonResponse
    {
        $record = Child::query()->forTenant($current->get())->find($child);

        if ($record === null) {
            return response()->json(['message' => 'Child not found.'], 404);
        }

        if ($record->checkIns()->exists()) {
            return response()->json(['message' => 'This child still has check-ins.'], 409);
        }

        $record->delete();

        return response()->json(null, 204);
    }
}
