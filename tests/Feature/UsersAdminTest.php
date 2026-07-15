<?php

namespace Tests\Feature;

use App\Livewire\Admin\ChangePassword;
use App\Livewire\Admin\Users;
use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UsersAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_staff' => false]);
    }

    /*
    |--------------------------------------------------------------------------
    | Access control
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_view_the_users_page(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->get(route('admin.users'))->assertOk();
    }

    public function test_staff_cannot_view_the_users_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false, 'is_staff' => true]), 'web');

        $this->get(route('admin.users'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_the_users_page(): void
    {
        $this->get(route('admin.users'))->assertRedirect(route('admin.login'));
    }

    public function test_any_staff_or_admin_can_reach_the_change_password_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false, 'is_staff' => true]), 'web');
        $this->get(route('admin.account.password'))->assertOk();

        $this->actingAs($this->admin(), 'web');
        $this->get(route('admin.account.password'))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_a_staff_user_with_a_hashed_password(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Sales Rep')
            ->set('email', 'rep@example.com')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->set('is_admin', false)
            ->set('is_staff', true)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'rep@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_staff);
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_creating_a_user_requires_a_role(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'No Role')
            ->set('email', 'norole@example.com')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->set('is_admin', false)
            ->set('is_staff', false)
            ->call('save')
            ->assertHasErrors('is_staff');

        $this->assertNull(User::where('email', 'norole@example.com')->first());
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Mismatch')
            ->set('email', 'mismatch@example.com')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'different-password')
            ->call('save')
            ->assertHasErrors('password');
    }

    public function test_editing_without_a_new_password_keeps_the_existing_one(): void
    {
        $this->actingAs($this->admin(), 'web');

        $target = User::factory()->create([
            'is_staff' => true,
            'password' => Hash::make('original-password'),
        ]);

        Livewire::test(Users::class)
            ->call('edit', $target->id)
            ->set('name', 'Renamed')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('Renamed', $target->name);
        $this->assertTrue(Hash::check('original-password', $target->password));
    }

    public function test_admin_can_reset_another_users_password(): void
    {
        $this->actingAs($this->admin(), 'web');

        $target = User::factory()->create(['is_staff' => true, 'password' => Hash::make('old')]);

        Livewire::test(Users::class)
            ->call('edit', $target->id)
            ->set('password', 'brand-new-password')
            ->set('password_confirmation', 'brand-new-password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-password', $target->fresh()->password));
    }

    /*
    |--------------------------------------------------------------------------
    | Safety guards
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_delete_another_user(): void
    {
        $this->actingAs($this->admin(), 'web');
        $target = User::factory()->create(['is_staff' => true]);

        Livewire::test(Users::class)->call('delete', $target->id);

        $this->assertNull(User::find($target->id));
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'web');

        Livewire::test(Users::class)->call('delete', $admin->id);

        $this->assertNotNull(User::find($admin->id));
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $admin = $this->admin(); // the only admin
        $this->actingAs($admin, 'web');

        Livewire::test(Users::class)
            ->call('edit', $admin->id)
            ->set('is_admin', false)
            ->set('is_staff', true)
            ->call('save')
            ->assertHasErrors('is_admin');

        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_a_non_last_admin_can_be_demoted(): void
    {
        $acting = $this->admin();
        $other = User::factory()->create(['is_admin' => true]);
        $this->actingAs($acting, 'web');

        Livewire::test(Users::class)
            ->call('edit', $other->id)
            ->set('is_admin', false)
            ->set('is_staff', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($other->fresh()->is_admin);
    }

    /*
    |--------------------------------------------------------------------------
    | Self-service password change
    |--------------------------------------------------------------------------
    */

    public function test_user_can_change_their_own_password(): void
    {
        $user = User::factory()->create(['is_staff' => true, 'password' => Hash::make('password')]);
        $this->actingAs($user, 'web');

        Livewire::test(ChangePassword::class)
            ->set('current_password', 'password')
            ->set('password', 'a-fresh-password')
            ->set('password_confirmation', 'a-fresh-password')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('a-fresh-password', $user->fresh()->password));
    }

    public function test_changing_password_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['is_staff' => true, 'password' => Hash::make('password')]);
        $this->actingAs($user, 'web');

        Livewire::test(ChangePassword::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'a-fresh-password')
            ->set('password_confirmation', 'a-fresh-password')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    /*
    |--------------------------------------------------------------------------
    | Campaign reset
    |--------------------------------------------------------------------------
    */

    public function test_reset_requires_typing_the_confirmation_word(): void
    {
        $this->actingAs($this->admin(), 'web');
        Player::factory()->count(2)->create();

        Livewire::test(Users::class)
            ->call('confirmReset')
            ->set('resetConfirm', 'nope')
            ->call('resetCampaign')
            ->assertHasErrors('resetConfirm');

        $this->assertSame(2, Player::count());
    }

    public function test_reset_wipes_player_and_winner_data_and_restores_inventory(): void
    {
        $this->actingAs($this->admin(), 'web');

        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => true,
            'inventory_quantity' => 3, // already decremented from 5 by the two spins below
        ]);
        $player = Player::factory()->create();

        foreach (range(1, 2) as $i) {
            $session = SpinSession::create([
                'campaign_id' => $campaign->id,
                'player_id' => $player->id,
                'prize_id' => $prize->id,
                'status' => SpinSession::STATUS_COMPLETED,
            ]);
            $result = SpinResult::create([
                'spin_session_id' => $session->id,
                'campaign_id' => $campaign->id,
                'player_id' => $player->id,
                'prize_id' => $prize->id,
            ]);
            Voucher::create([
                'spin_result_id' => $result->id,
                'campaign_id' => $campaign->id,
                'player_id' => $player->id,
                'prize_id' => $prize->id,
                'code' => "VC-{$i}",
                'status' => Voucher::STATUS_PENDING,
                'expires_at' => now()->addDay(),
            ]);
        }

        Livewire::test(Users::class)
            ->call('confirmReset')
            ->set('resetConfirm', 'reset') // case-insensitive
            ->call('resetCampaign')
            ->assertHasNoErrors();

        $this->assertSame(0, Player::count());
        $this->assertSame(0, SpinSession::count());
        $this->assertSame(0, SpinResult::count());
        $this->assertSame(0, Voucher::count());

        // 3 remaining + 2 reserved units restored = 5.
        $this->assertSame(5, $prize->fresh()->inventory_quantity);
    }

    public function test_reset_leaves_untracked_prize_inventory_alone(): void
    {
        $this->actingAs($this->admin(), 'web');

        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => false,
            'inventory_quantity' => null,
        ]);
        $player = Player::factory()->create();
        SpinSession::create([
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'status' => SpinSession::STATUS_COMPLETED,
        ]);

        Livewire::test(Users::class)
            ->call('confirmReset')
            ->set('resetConfirm', 'RESET')
            ->call('resetCampaign')
            ->assertHasNoErrors();

        $this->assertNull($prize->fresh()->inventory_quantity);
    }
}
