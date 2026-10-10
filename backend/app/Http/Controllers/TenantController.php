<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(): JsonResponse
    {
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);

        return response()->json($tenants);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $tenant = Tenant::query()->create($data);

        return response()->json($tenant->only(['id', 'name']), 201);
    }

    public function update(Request $request, int $tenant): JsonResponse
    {
        $record = Tenant::query()->find($tenant);

        if ($record === null) {
            return response()->json(['message' => 'Nursery group not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $record->update($data);

        return response()->json($record->only(['id', 'name']));
    }

    public function destroy(int $tenant): JsonResponse
    {
        $record = Tenant::query()->find($tenant);

        if ($record === null) {
            return response()->json(['message' => 'Nursery group not found.'], 404);
        }

        if ($record->rooms()->exists() || $record->children()->exists()) {
            return response()->json([
                'message' => 'This nursery group still has rooms or children.',
            ], 409);
        }

        $record->delete();

        return response()->json(null, 204);
    }
}
