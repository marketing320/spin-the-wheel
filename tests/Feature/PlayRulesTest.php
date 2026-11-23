<?php

namespace Tests\Feature;

use App\Livewire\Admin\PlayRules;
use App\Models\Campaign;
use App\Models\Player;
use App\Models\PlayRule;
use App\Models\Prize;
use App\Models\User;
use App\Services\SpinLockService;
use App\Services\SpinService;
use App\Services\SpinEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlayRulesTest extends TestCase
{
    use RefreshDatabase;

    public static function ruleTypes(): array
    {
        return [
            'once per campaign' => [PlayRule::TYPE_ONCE_PER_CAMPAIGN, null],
            'once per day' => [PlayRule::TYPE_ONCE_PER_DAY, null],
            'cooldown' => [PlayRule::TYPE_EVERY_X_HOURS, 'cooldown_hours'],
            'max per campaign' => [PlayRule::TYPE_MAX_PER_CAMPAIGN, 'max_spins_per_campaign'],
            'max per day' => [PlayRule::TYPE_MAX_PER_DAY, 'max_spins_per_day'],
        ];
    }

    #[DataProvider('ruleTypes')]
    public function test_each_rule_can_be_saved_enforced_disabled_and_deleted(string $type, ?string $field): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();
        $player = Player::factory()->create();
        $spins = app(SpinService::class);
        $spins->complete($spins->start($player));
        $this->travel(30)->seconds();
        app(SpinLockService::class)->currentActive();
        $eligibility = app(SpinEligibilityService::class);

        $form = Livewire::actingAs(User::factory()->create(['is_admin' => true]), 'web')
            ->test(PlayRules::class)->call('create')->set('rule_type', $type);

        if ($field) {
            $form->call('save')->assertHasErrors([$field => 'required_if']);
            $this->assertDatabaseCount('play_rules', 0);
            $form->set($field, 0)->call('save')->assertHasErrors([$field => 'min']);
            $form->set($field, 1);
        }

        $form->call('save')->assertHasNoErrors();
        $rule = $campaign->playRules()->sole();
        $this->assertFalse($eligibility->check($player, $campaign)['eligible']);

        $form->call('edit', $rule->id)->set('is_active', false)->call('save')->assertHasNoErrors();
        $this->assertTrue($eligibility->check($player, $campaign)['eligible']);

        $form->set('is_active', true)->call('save')->assertHasNoErrors();
        $this->assertFalse($eligibility->check($player, $campaign)['eligible']);

        if ($type === PlayRule::TYPE_EVERY_X_HOURS) {
            $this->travel(1)->hours();
            $this->assertTrue($eligibility->check($player, $campaign)['eligible']);
        } elseif ($type === PlayRule::TYPE_ONCE_PER_DAY || $type === PlayRule::TYPE_MAX_PER_DAY) {
            $this->travel(1)->days();
            $this->assertTrue($eligibility->check($player, $campaign)['eligible']);
        } elseif ($type === PlayRule::TYPE_MAX_PER_CAMPAIGN) {
            $form->set($field, 2)->call('save')->assertHasNoErrors();
            $this->assertTrue($eligibility->check($player, $campaign)['eligible']);
        }

        $form->call('delete', $rule->id);
        $this->assertTrue($eligibility->check($player, $campaign)['eligible']);
    }

    public function test_saved_admin_rule_changes_apply_to_the_next_player_request(): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();
        $player = Player::factory()->create();
        $rule = $campaign->playRules()->create([
            'rule_type' => PlayRule::TYPE_MAX_PER_DAY,
            'max_spins_per_day' => 1,
            'is_active' => true,
        ]);

        $spins = app(SpinService::class);
        $spins->complete($spins->start($player));
        $this->travel(30)->seconds();
        app(SpinLockService::class)->currentActive();

        $this->actingAs($player, 'player')
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) Mobile')
            ->getJson(route('spin.eligibility'))
            ->assertOk()
            ->assertJsonPath('eligible', false)
            ->assertJsonPath('reason', 'max_per_day');

        $admin = User::factory()->create(['is_admin' => true]);
        $form = Livewire::actingAs($admin, 'web')->test(PlayRules::class)
            ->call('edit', $rule->id)
            ->set('max_spins_per_day', 2)
            ->call('save')
            ->assertHasNoErrors();

        $this->actingAs($player, 'player')
            ->getJson(route('spin.eligibility'))
            ->assertOk()
            ->assertJsonPath('eligible', true);

        $form->set('max_spins_per_day', 1)->call('save')->assertHasNoErrors();

        $this->actingAs($player, 'player')
            ->getJson(route('spin.eligibility'))
            ->assertOk()
            ->assertJsonPath('eligible', false);

        $form->set('is_active', false)->call('save')->assertHasNoErrors();

        $this->actingAs($player, 'player')
            ->getJson(route('spin.eligibility'))
            ->assertOk()
            ->assertJsonPath('eligible', true);
    }
}
