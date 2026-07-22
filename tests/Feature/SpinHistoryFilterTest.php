<?php

namespace Tests\Feature;

use App\Livewire\Admin\Spins;
use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SpinHistoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_staff' => false]);
    }

    private function spin(Campaign $campaign, Player $player, Prize $prize): SpinSession
    {
        return SpinSession::create([
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'status' => SpinSession::STATUS_COMPLETED,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function test_admin_can_filter_spins_by_prize(): void
    {
        $campaign = Campaign::factory()->create();
        $iphone = Prize::factory()->for($campaign)->create(['name' => 'iPhone']);
        $mug = Prize::factory()->for($campaign)->create(['name' => 'Mug']);
        $alice = Player::factory()->create(['email' => 'alice@example.com']);
        $bob = Player::factory()->create(['email' => 'bob@example.com']);

        $iphoneSpin = $this->spin($campaign, $alice, $iphone);
        $mugSpin = $this->spin($campaign, $bob, $mug);

        $this->actingAs($this->admin(), 'web');

        $ids = collect(
            Livewire::test(Spins::class)->set('prizeId', $iphone->id)->viewData('spins')->items()
        )->pluck('id');

        $this->assertTrue($ids->contains($iphoneSpin->id));
        $this->assertFalse($ids->contains($mugSpin->id));
    }

    public function test_search_matches_player_display_name_without_over_matching(): void
    {
        // Regression guard: the display-name OR clause must stay inside the
        // player relationship correlation. If it leaks, matching one player's
        // name would return every spin.
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create();

        $alice = Player::factory()->create(['email' => 'alice@example.com', 'display_name' => 'Zephyr Quinn']);
        $bob = Player::factory()->create(['email' => 'bob@example.com', 'display_name' => 'Bob Builder']);

        $aliceSpin = $this->spin($campaign, $alice, $prize);
        $bobSpin = $this->spin($campaign, $bob, $prize);

        $this->actingAs($this->admin(), 'web');

        $ids = collect(
            Livewire::test(Spins::class)->set('search', 'Zephyr')->viewData('spins')->items()
        )->pluck('id');

        $this->assertTrue($ids->contains($aliceSpin->id));
        $this->assertFalse($ids->contains($bobSpin->id));
    }

    public function test_search_still_matches_player_email(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create();
        $alice = Player::factory()->create(['email' => 'alice@example.com', 'display_name' => null]);
        $bob = Player::factory()->create(['email' => 'bob@example.com', 'display_name' => null]);

        $aliceSpin = $this->spin($campaign, $alice, $prize);
        $bobSpin = $this->spin($campaign, $bob, $prize);

        $this->actingAs($this->admin(), 'web');

        $ids = collect(
            Livewire::test(Spins::class)->set('search', 'alice@')->viewData('spins')->items()
        )->pluck('id');

        $this->assertTrue($ids->contains($aliceSpin->id));
        $this->assertFalse($ids->contains($bobSpin->id));
    }

    public function test_changing_campaign_clears_the_prize_filter(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create();

        $this->actingAs($this->admin(), 'web');

        Livewire::test(Spins::class)
            ->set('prizeId', $prize->id)
            ->set('campaignId', $campaign->id)
            ->assertSet('prizeId', null);
    }

    public function test_csv_export_respects_the_prize_filter(): void
    {
        $campaign = Campaign::factory()->create();
        $iphone = Prize::factory()->for($campaign)->create(['name' => 'iPhone']);
        $mug = Prize::factory()->for($campaign)->create(['name' => 'Mug']);
        $alice = Player::factory()->create(['email' => 'alice@example.com']);
        $bob = Player::factory()->create(['email' => 'bob@example.com']);

        $this->spin($campaign, $alice, $iphone);
        $this->spin($campaign, $bob, $mug);

        $this->actingAs($this->admin(), 'web');

        $response = $this->get(route('admin.spins.export', ['prize_id' => $iphone->id]));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('alice@example.com', $content);
        $this->assertStringNotContainsString('bob@example.com', $content);
    }
}
