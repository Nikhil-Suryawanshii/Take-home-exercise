<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_nursery_groups_can_be_listed_and_created_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Group A']);

        $this->getJson('/api/tenants')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Group A']);

        $this->postJson('/api/tenants', ['name' => 'Little Oaks'])
            ->assertCreated()
            ->assertJsonPath('name', 'Little Oaks');
    }

    public function test_rooms_and_children_can_be_managed_by_name(): void
    {
        $tenant = Tenant::factory()->create();

        $roomId = $this->asTenant($tenant)
            ->postJson('/api/rooms', ['name' => 'Sunflowers'])
            ->assertCreated()
            ->json('id');

        $this->asTenant($tenant)
            ->putJson('/api/rooms/'.$roomId, ['name' => 'Sunflower Room'])
            ->assertOk()
            ->assertJsonPath('name', 'Sunflower Room');

        $childId = $this->asTenant($tenant)
            ->postJson('/api/children', ['name' => 'Ava'])
            ->assertCreated()
            ->json('id');

        $this->asTenant($tenant)
            ->getJson('/api/rooms')
            ->assertOk()
            ->assertJsonFragment(['id' => $roomId, 'name' => 'Sunflower Room']);

        $this->asTenant($tenant)
            ->getJson('/api/children')
            ->assertOk()
            ->assertJsonFragment(['id' => $childId, 'name' => 'Ava']);

        $this->asTenant($tenant)
            ->deleteJson('/api/children/'.$childId)
            ->assertNoContent();

        $this->asTenant($tenant)
            ->deleteJson('/api/rooms/'.$roomId)
            ->assertNoContent();
    }

    public function test_tenant_cannot_see_or_change_another_tenants_rooms_or_children(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $roomB = Room::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Little Explorers']);
        $childB = Child::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Noah']);
        Room::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Sunflowers']);

        $this->asTenant($tenantA)
            ->getJson('/api/rooms')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Sunflowers'])
            ->assertJsonMissing(['name' => 'Little Explorers']);

        $this->asTenant($tenantA)
            ->getJson('/api/children')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Noah']);

        $this->asTenant($tenantA)
            ->putJson('/api/rooms/'.$roomB->id, ['name' => 'Taken'])
            ->assertNotFound();

        $this->asTenant($tenantA)
            ->deleteJson('/api/children/'.$childB->id)
            ->assertNotFound();
    }

    private function asTenant(Tenant $tenant): static
    {
        return $this->withHeader('X-Tenant-ID', (string) $tenant->id);
    }
}
