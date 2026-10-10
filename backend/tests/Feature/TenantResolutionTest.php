<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_tenant_header_is_rejected(): void
    {
        $this->getJson('/api/rooms/1/occupants')->assertUnauthorized();
    }

    public function test_unknown_tenant_is_rejected(): void
    {
        $this->withHeader('X-Tenant-ID', '999999')
            ->getJson('/api/rooms/1/occupants')
            ->assertUnauthorized();
    }

    public function test_known_tenant_reaches_the_route(): void
    {
        $tenant = Tenant::factory()->create();

        $this->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->getJson('/api/rooms/999999/occupants')
            ->assertNotFound();
    }

    public function test_home_route_does_not_require_a_tenant(): void
    {
        $this->get('/')->assertOk();
    }
}
