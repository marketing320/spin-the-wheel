<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\WheelDesign;
use App\Models\Campaign;
use App\Models\FormField;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_celebration_image_confetti_settings_persist(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(AdminSettings::class)
            ->set('celebration_image_enabled', true)
            ->set('celebration_image_count', 40)
            ->set('celebration_image_size', 60)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue((bool) \App\Support\Settings::get('celebration.image_enabled'));
        $this->assertSame(40, (int) \App\Support\Settings::get('celebration.image_count'));
        $this->assertSame(60, (int) \App\Support\Settings::get('celebration.image_size'));
    }

    public function test_players_csv_export_downloads_selected_and_all(): void
    {
        $this->actingAs($this->admin(), 'web');

        $players = \App\Models\Player::factory()->count(3)->create();

        // Export all matching.
        $this->get(route('admin.players.export', ['all' => 1]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Export a specific selection.
        $this->get(route('admin.players.export', ['ids' => $players->take(2)->pluck('id')->implode(',')]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_wheel_design_duration_is_fixed_at_eight_seconds(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(WheelDesign::class)
            ->assertSet('animation_duration_ms', 8000)
            ->set('animation_duration_ms', 11000)
            ->call('save')
            ->assertHasErrors(['animation_duration_ms']);
    }

    public function test_default_spin_duration_is_configurable(): void
    {
        $this->actingAs($this->admin(), 'web');

        // A valid value within range saves and persists.
        Livewire::test(AdminSettings::class)
            ->assertSet('spin_default_duration_ms', 8000)
            ->set('spin_default_duration_ms', 11000)
            ->call('save')
            ->assertHasNoErrors(['spin_default_duration_ms']);

        $this->assertSame(11000, (int) \App\Support\Settings::get('spin.default_duration_ms'));

        // Out-of-range values are rejected.
        Livewire::test(AdminSettings::class)
            ->set('spin_default_duration_ms', 999999)
            ->call('save')
            ->assertHasErrors(['spin_default_duration_ms']);
    }
}
