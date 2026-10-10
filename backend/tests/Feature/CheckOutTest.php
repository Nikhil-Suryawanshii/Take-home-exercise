<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_out_removes_the_child_from_occupants(): void
    {
        [$tenant, $room, $child] = $this->nursery();
        $checkInId = $this->checkIn($tenant, $room, $child);

        $this->asTenant($tenant)
            ->postJson('/api/check-ins/'.$checkInId.'/check-out')
            ->assertOk()
            ->assertJsonPath('id', $checkInId)
            ->assertJsonPath('room_id', $room->id)
            ->assertJsonPath('child_id', $child->id);

        $this->asTenant($tenant)
            ->getJson('/api/rooms/'.$room->id.'/occupants')
            ->assertOk()
            ->assertExactJson([]);

        $this->assertNotNull(CheckIn::query()->find($checkInId)?->checked_out_at);
    }

    public function test_unknown_check_in_is_not_found(): void
    {
        [$tenant] = $this->nursery();

        $this->asTenant($tenant)
            ->postJson('/api/check-ins/999999/check-out')
            ->assertNotFound();
    }

    public function test_second_check_out_is_rejected_and_keeps_the_timestamp(): void
    {
        [$tenant, $room, $child] = $this->nursery();
        $checkInId = $this->checkIn($tenant, $room, $child);

        $this->asTenant($tenant)
            ->postJson('/api/check-ins/'.$checkInId.'/check-out')
            ->assertOk();

        $checkedOutAt = CheckIn::query()->findOrFail($checkInId)->checked_out_at;

        $this->travel(5)->minutes();

        $this->asTenant($tenant)
            ->postJson('/api/check-ins/'.$checkInId.'/check-out')
            ->assertStatus(409);

        $this->assertTrue(
            CheckIn::query()->findOrFail($checkInId)->checked_out_at?->equalTo($checkedOutAt)
        );
    }

    public function test_child_can_be_checked_in_again_after_check_out(): void
    {
        [$tenant, $room, $child] = $this->nursery();
        $otherRoom = Room::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Little Explorers',
        ]);
        $checkInId = $this->checkIn($tenant, $room, $child);

        $this->asTenant($tenant)
            ->postJson('/api/check-ins/'.$checkInId.'/check-out')
            ->assertOk();

        $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$otherRoom->id.'/check-ins', ['child_id' => $child->id])
            ->assertCreated()
            ->assertJsonPath('room_id', $otherRoom->id);

        $this->asTenant($tenant)
            ->getJson('/api/rooms/'.$otherRoom->id.'/occupants')
            ->assertOk()
            ->assertJsonPath('0.child_id', $child->id);
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

    private function checkIn(Tenant $tenant, Room $room, Child $child): int
    {
        return $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $child->id])
            ->assertCreated()
            ->json('id');
    }
}
