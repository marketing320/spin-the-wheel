<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\FormField;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Give the config pages an active campaign to operate on.
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();
        FormField::factory()->for($campaign)->create();
    }

    public function test_every_admin_page_renders_for_an_admin(): void
    {
        $this->actingAs($this->admin(), 'web');

        $routes = [
            'admin.dashboard', 'admin.campaigns', 'admin.prizes', 'admin.wheel',
            'admin.play-rules', 'admin.forms', 'admin.geofence', 'admin.live-view',
            'admin.spins', 'admin.players', 'admin.settings',
        ];

        foreach ($routes as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_non_admin_cannot_access_admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]), 'web');

        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_spins_csv_export_downloads(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->get(route('admin.spins.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
