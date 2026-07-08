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
            'admin.spins', 'admin.players', 'admin.settings', 'admin.redeem',
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

    public function test_staff_can_log_in_through_the_admin_login_page(): void
    {
        $staff = User::factory()->create([
            'is_admin' => false,
            'is_staff' => true,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        Livewire::test(\App\Livewire\Admin\Login::class)
            ->set('email', $staff->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($staff, 'web');
    }

    public function test_user_without_admin_or_staff_cannot_log_in(): void
    {
        $plain = User::factory()->create([
            'is_admin' => false,
            'is_staff' => false,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        Livewire::test(\App\Livewire\Admin\Login::class)
            ->set('email', $plain->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest('web');
    }

    public function test_staff_can_access_only_the_limited_surface(): void
    {
        $staff = User::factory()->create(['is_admin' => false, 'is_staff' => true]);
        $this->actingAs($staff, 'web');

        foreach (['admin.dashboard', 'admin.spins', 'admin.redeem'] as $name) {
            $this->get(route($name))->assertOk();
        }

        foreach ([
            'admin.campaigns', 'admin.prizes', 'admin.wheel', 'admin.play-rules',
            'admin.forms', 'admin.geofence', 'admin.live-view', 'admin.players', 'admin.settings',
        ] as $name) {
            $this->get(route($name))->assertForbidden();
        }
    }

    public function test_plain_user_cannot_access_staff_surface(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false, 'is_staff' => false]), 'web');

        $this->get(route('admin.redeem'))->assertForbidden();
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

    public function test_prize_can_be_marked_as_voucher_with_expiry_override(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(\App\Livewire\Admin\Prizes::class)
            ->call('create')
            ->set('name', 'Free Coffee')
            ->set('type', 'voucher')
            ->set('voucher_expiry_hours', 6)
            ->call('save')
            ->assertHasNoErrors();

        $prize = Prize::where('name', 'Free Coffee')->first();
        $this->assertNotNull($prize);
        $this->assertTrue($prize->isVoucher());
        $this->assertSame(6, $prize->voucher_expiry_hours);
    }

    public function test_blank_number_fields_save_as_null_instead_of_empty_string(): void
    {
        // Regression: wire:model sends a cleared number input as '' (not
        // null), and '' bound into an integer/decimal column previously blew
        // up with a strict-mode MySQL error instead of saving as NULL.
        $this->actingAs($this->admin(), 'web');

        Livewire::test(\App\Livewire\Admin\Prizes::class)
            ->call('create')
            ->set('name', 'Mystery Voucher')
            ->set('type', 'voucher')
            ->set('voucher_expiry_hours', '')
            ->set('win_percentage', '')
            ->set('weight', '')
            ->set('inventory_quantity', '')
            ->call('save')
            ->assertHasNoErrors();

        $prize = Prize::where('name', 'Mystery Voucher')->first();
        $this->assertNotNull($prize);
        $this->assertNull($prize->voucher_expiry_hours);
        $this->assertNull($prize->win_percentage);
        $this->assertNull($prize->weight);
        $this->assertNull($prize->inventory_quantity);
    }

    public function test_admin_can_sort_prizes_by_name_in_both_directions(): void
    {
        $this->actingAs($this->admin(), 'web');
        $campaign = Campaign::current();

        Prize::factory()->for($campaign)->create(['name' => 'Zebra Prize']);
        Prize::factory()->for($campaign)->create(['name' => 'Apple Prize']);
        Prize::factory()->for($campaign)->create(['name' => 'Mango Prize']);

        $component = Livewire::test(\App\Livewire\Admin\Prizes::class)->call('sortBy', 'name');

        $names = fn () => collect($component->viewData('prizes'))
            ->pluck('name')
            ->filter(fn ($n) => str_ends_with($n, 'Prize'))
            ->values()
            ->all();

        $this->assertSame(['Apple Prize', 'Mango Prize', 'Zebra Prize'], $names());

        $component->call('sortBy', 'name'); // same column again -> flips to descending
        $this->assertSame(['Zebra Prize', 'Mango Prize', 'Apple Prize'], $names());
    }

    public function test_admin_can_sort_prizes_by_rarity_rank_not_alphabetically(): void
    {
        // "rare" sorts before "uncommon" alphabetically but ranks *higher*
        // (uncommon=1, rare=2) — a great discriminator between rank-based
        // and plain alphabetical sorting.
        $this->actingAs($this->admin(), 'web');
        $campaign = Campaign::current();

        Prize::factory()->for($campaign)->create(['name' => 'Rare Item', 'rarity' => 'rare']);
        Prize::factory()->for($campaign)->create(['name' => 'Uncommon Item', 'rarity' => 'uncommon']);
        Prize::factory()->for($campaign)->create(['name' => 'Common Item', 'rarity' => 'common']);

        $component = Livewire::test(\App\Livewire\Admin\Prizes::class)->call('sortBy', 'rarity');

        $names = collect($component->viewData('prizes'))
            ->pluck('name')
            ->filter(fn ($n) => str_ends_with($n, 'Item'))
            ->values();

        $this->assertSame(['Common Item', 'Uncommon Item', 'Rare Item'], $names->all());
    }

    public function test_admin_can_duplicate_a_prize(): void
    {
        $this->actingAs($this->admin(), 'web');
        $campaign = Campaign::current();

        $original = Prize::factory()->for($campaign)->create([
            'name' => 'Grand Prize',
            'is_active' => true,
            'weight' => 5,
        ]);

        Livewire::test(\App\Livewire\Admin\Prizes::class)->call('duplicate', $original->id);

        $duplicates = Prize::where('name', 'Grand Prize')->get();
        $this->assertCount(2, $duplicates);

        $clone = $duplicates->firstWhere('id', '!=', $original->id);
        $this->assertNotNull($clone);
        $this->assertFalse($clone->is_active);
        $this->assertSame(5, $clone->weight);
    }

    public function test_global_voucher_expiry_setting_persists(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(AdminSettings::class)
            ->assertSet('voucher_expiry_hours', 24)
            ->set('voucher_expiry_hours', 48)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(48, (int) \App\Support\Settings::get('redemption.voucher_expiry_hours'));
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
