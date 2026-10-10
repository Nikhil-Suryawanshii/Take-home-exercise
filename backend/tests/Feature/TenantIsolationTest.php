<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_list_another_tenants_occupants(): void
    {
        [$tenantA, $other] = $this->twoNurseries();

        $this->asTenant($tenantA)
            ->getJson('/api/rooms/'.$other['room']->id.'/occupants')
            ->assertNotFound();
    }

    public function test_tenant_cannot_check_into_another_tenants_room(): void
    {
        [$tenantA, $other] = $this->twoNurseries();

        $this->asTenant($tenantA)
            ->postJson('/api/rooms/'.$other['room']->id.'/check-ins', [
                'child_id' => $other['child']->id,
            ])
            ->assertNotFound();
    }

    public function test_tenant_cannot_check_out_another_tenants_stay(): void
    {
        [$tenantA, $other] = $this->twoNurseries();

        $this->asTenant($tenantA)
            ->postJson('/api/check-ins/'.$other['checkIn']->id.'/check-out')
            ->assertNotFound();
    }

    public function test_tenant_cannot_check_in_another_tenants_child(): void
    {
        [$tenantA, $other] = $this->twoNurseries();
        $roomA = Room::query()->where('tenant_id', $tenantA->id)->firstOrFail();

        $this->asTenant($tenantA)
            ->postJson('/api/rooms/'.$roomA->id.'/check-ins', [
                'child_id' => $other['child']->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('check_ins', [
            'room_id' => $roomA->id,
            'child_id' => $other['child']->id,
        ]);
    }

    public function test_missing_tenant_header_is_rejected(): void
    {
        $this->getJson('/api/rooms/1/occupants')->assertUnauthorized();
    }

    /**
     * @return array{0: Tenant, 1: array{room: Room, child: Child, checkIn: CheckIn}}
     */
    private function twoNurseries(): array
    {
        $tenantA = Tenant::factory()->create();
        $this->nurseryFor($tenantA);

        $tenantB = Tenant::factory()->create();

        return [$tenantA, $this->nurseryFor($tenantB)];
    }

    /**
     * @return array{room: Room, child: Child, checkIn: CheckIn}
     */
    private function nurseryFor(Tenant $tenant): array
    {
        $room = Room::factory()->create(['tenant_id' => $tenant->id]);
        $child = Child::factory()->create(['tenant_id' => $tenant->id]);
        $checkIn = CheckIn::factory()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'child_id' => $child->id,
        ]);

        return [
            'room' => $room,
            'child' => $child,
            'checkIn' => $checkIn,
        ];
    }

    private function asTenant(Tenant $tenant): static
    {
        return $this->withHeader('X-Tenant-ID', (string) $tenant->id);
    }
}
