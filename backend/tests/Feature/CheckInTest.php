<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_checks_a_child_into_a_room(): void
    {
        [$tenant, $room, $child] = $this->nursery();

        $response = $this->asTenant($tenant)->postJson(
            '/api/rooms/'.$room->id.'/check-ins',
            ['child_id' => $child->id],
        );

        $response->assertCreated()
            ->assertJsonPath('room_id', $room->id)
            ->assertJsonPath('child_id', $child->id);

        $this->assertDatabaseHas('check_ins', [
            'id' => $response->json('id'),
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_out_at' => null,
        ]);

        $this->asTenant($tenant)
            ->getJson('/api/rooms/'.$room->id.'/occupants')
            ->assertOk()
            ->assertJsonPath('0.check_in_id', $response->json('id'))
            ->assertJsonPath('0.child_id', $child->id)
            ->assertJsonPath('0.name', $child->name);
    }

    public function test_second_check_in_to_the_same_room_is_rejected(): void
    {
        [$tenant, $room, $child] = $this->nursery();

        $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $child->id])
            ->assertCreated();

        $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $child->id])
            ->assertStatus(409);

        $this->assertSame(1, $this->openStays($child));
    }

    public function test_check_in_to_another_room_is_rejected(): void
    {
        [$tenant, $room, $child] = $this->nursery();
        $otherRoom = Room::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Little Explorers',
        ]);

        $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $child->id])
            ->assertCreated();

        $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$otherRoom->id.'/check-ins', ['child_id' => $child->id])
            ->assertStatus(409);

        $this->assertSame(1, $this->openStays($child));
        $this->assertDatabaseHas('check_ins', [
            'child_id' => $child->id,
            'room_id' => $room->id,
            'checked_out_at' => null,
        ]);
    }

    public function test_unknown_room_is_not_found(): void
    {
        [$tenant, , $child] = $this->nursery();

        $this->asTenant($tenant)
            ->postJson('/api/rooms/999999/check-ins', ['child_id' => $child->id])
            ->assertNotFound();

        $this->assertSame(0, $this->openStays($child));
    }

    public function test_unknown_child_is_not_found(): void
    {
        [$tenant, $room] = $this->nursery();

        $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => 999999])
            ->assertNotFound();

        $this->assertSame(0, CheckIn::query()->count());
    }

    public function test_child_from_another_tenant_is_not_found(): void
    {
        [$tenant, $room] = $this->nursery();
        $otherChild = Child::factory()->create();

        $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', ['child_id' => $otherChild->id])
            ->assertNotFound();

        $this->assertSame(0, CheckIn::query()->count());
    }

    public function test_missing_child_id_is_rejected(): void
    {
        [$tenant, $room] = $this->nursery();

        $this->asTenant($tenant)
            ->postJson('/api/rooms/'.$room->id.'/check-ins', [])
            ->assertUnprocessable();

        $this->assertSame(0, CheckIn::query()->count());
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

    private function openStays(Child $child): int
    {
        return CheckIn::query()
            ->where('child_id', $child->id)
            ->whereNull('checked_out_at')
            ->count();
    }
}
