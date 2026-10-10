<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccupantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_checked_in_child_is_listed(): void
    {
        [$tenant, $room, $child] = $this->nursery();

        $checkInId = $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $child->id])
            ->assertCreated()
            ->json('id');

        $this->asTenant($tenant)
            ->getJson('/api/rooms/'.$room->id.'/occupants')
            ->assertOk()
            ->assertExactJson([
                [
                    'check_in_id' => $checkInId,
                    'child_id' => $child->id,
                    'name' => $child->name,
                ],
            ]);
    }

    public function test_empty_room_returns_an_empty_list(): void
    {
        [$tenant, $room] = $this->nursery();

        $this->asTenant($tenant)
            ->getJson('/api/rooms/'.$room->id.'/occupants')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_closed_stay_is_not_listed(): void
    {
        [$tenant, $room, $child] = $this->nursery();

        CheckIn::factory()->closed()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
        ]);

        $this->asTenant($tenant)
            ->getJson('/api/rooms/'.$room->id.'/occupants')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_child_in_another_room_is_not_listed(): void
    {
        [$tenant, $room, $child] = $this->nursery();
        $otherRoom = Room::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Little Explorers',
        ]);

        CheckIn::factory()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $otherRoom->id,
            'child_id' => $child->id,
        ]);

        $this->asTenant($tenant)
            ->getJson('/api/rooms/'.$room->id.'/occupants')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_another_tenants_room_is_not_found(): void
    {
        [$tenant] = $this->nursery();
        $otherRoom = Room::factory()->create();

        $this->asTenant($tenant)
            ->getJson('/api/rooms/'.$otherRoom->id.'/occupants')
            ->assertNotFound();
    }

    /**
     * @return array{0: Tenant, 1: Room, 2: Child}
     */
    private function nursery(): array
    {
        $tenant = Tenant::factory()->create();
        $room = Room::factory()->create(['tenant_id' => $tenant->id]);
        $child = Child::factory()->create(['tenant_id' => $tenant->id]);

        return [$tenant, $room, $child];
    }

    private function asTenant(Tenant $tenant): static
    {
        return $this->withHeader('X-Tenant-ID', (string) $tenant->id);
    }
}
