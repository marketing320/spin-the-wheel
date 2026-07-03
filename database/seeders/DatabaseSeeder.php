<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\FormField;
use App\Models\GeofenceSetting;
use App\Models\PlayRule;
use App\Models\Prize;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmins();
        $campaign = $this->seedCampaign();
        $this->seedPrizes($campaign);
        $this->seedFormFields($campaign);
        $this->seedPlayRule($campaign);
        $this->seedGeofence($campaign);
        $this->seedSettings();
    }

    protected function seedAdmins(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@brightstarcomp.com'],
            ['name' => 'Administrator', 'password' => Hash::make('password'), 'is_admin' => true]
        );

        User::updateOrCreate(
            ['email' => 'marketing@brightstarcomp.com'],
            ['name' => 'Brightstar Marketing', 'password' => Hash::make('password'), 'is_admin' => true]
        );
    }

    protected function seedCampaign(): Campaign
    {
        // Only one active campaign at a time.
        Campaign::query()->update(['active' => false]);

        return Campaign::updateOrCreate(
            ['slug' => 'grand-launch-2026'],
            [
                'name' => 'Grand Launch Giveaway',
                'status' => Campaign::STATUS_ACTIVE,
                'active' => true,
                'prize_mode' => Campaign::MODE_WEIGHTED,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'settings' => [
                    'wheel' => [
                        'label_style' => 'light',
                        'pointer_color' => '#ffffff',
                        'hub_logo' => '🎡',
                        'background_style' => 'aurora',
                        'animation_duration_ms' => 8000,
                        'sound_enabled' => false,
                        'glow_intensity' => 65,
                        'three_intensity' => 75,
                    ],
                ],
            ]
        );
    }

    protected function seedPrizes(Campaign $campaign): void
    {
        // Flat arcade palette (no gradients): blue, green, teal, purple, yellow.
        $prizes = [
            ['Thank You Voucher', 'common', '#0e75bc', 55, 'light', 'Show this screen at the counter to claim your voucher.'],
            ['Small Gift', 'uncommon', '#24b26b', 25, 'medium', 'Collect your gift at the registration desk.'],
            ['Discount Voucher', 'rare', '#12a5b0', 12, 'strong', 'Use code SPIN20 for 20% off your next purchase.'],
            ['Premium Accessory', 'epic', '#7b5cff', 6, 'heavy', 'Our team will contact you to arrange delivery.'],
            ['Grand Prize', 'legendary', '#f6c31c', 2, 'max', 'Congratulations! Please see the event manager to claim your grand prize.'],
        ];

        foreach ($prizes as $i => [$name, $rarity, $color, $weight, $confetti, $redemption]) {
            Prize::updateOrCreate(
                ['campaign_id' => $campaign->id, 'name' => $name],
                [
                    'description' => null,
                    'rarity' => $rarity,
                    'color' => $color,
                    'weight' => $weight,
                    'win_percentage' => $weight, // usable if switched to strict mode
                    'inventory_enabled' => $rarity === 'legendary',
                    'inventory_quantity' => $rarity === 'legendary' ? 3 : null,
                    'confetti_level' => $confetti,
                    'redemption_message' => $redemption,
                    'is_active' => true,
                    'sort_order' => $i,
                ]
            );
        }
    }

    protected function seedFormFields(Campaign $campaign): void
    {
        $fields = [
            ['Full name', 'full_name', 'text', 'Jane Doe', true, null],
            ['Phone number', 'phone', 'phone', '+60 12-345 6789', true, null],
            ['Branch / event location', 'branch', 'select', null, true, [
                ['label' => 'Kuala Lumpur', 'value' => 'kuala_lumpur'],
                ['label' => 'Penang', 'value' => 'penang'],
                ['label' => 'Johor Bahru', 'value' => 'johor_bahru'],
            ]],
            ['I agree to the terms and to be contacted about my prize', 'consent', 'consent', null, true, null],
        ];

        foreach ($fields as $i => [$label, $key, $type, $placeholder, $required, $options]) {
            FormField::updateOrCreate(
                ['campaign_id' => $campaign->id, 'field_key' => $key],
                [
                    'label' => $label,
                    'field_type' => $type,
                    'placeholder' => $placeholder,
                    'options' => $options,
                    'is_required' => $required,
                    'sort_order' => $i,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedPlayRule(Campaign $campaign): void
    {
        PlayRule::updateOrCreate(
            ['campaign_id' => $campaign->id, 'rule_type' => PlayRule::TYPE_ONCE_PER_DAY],
            ['is_active' => true]
        );
    }

    protected function seedGeofence(Campaign $campaign): void
    {
        GeofenceSetting::updateOrCreate(
            ['campaign_id' => $campaign->id],
            [
                'enabled' => false, // disabled so the demo works anywhere
                'location_name' => 'Brightstar Flagship Store',
                'latitude' => 3.139003,
                'longitude' => 101.686855,
                'radius_meters' => 150,
                'blocked_message' => 'Please visit our event booth to spin the wheel!',
            ]
        );
    }

    protected function seedSettings(): void
    {
        Settings::setMany([
            'branding.app_name' => 'Spin The Wheel',
            'branding.tagline' => 'Spin to win amazing prizes — good luck!',
            'live_view.idle_message' => 'Step up and spin to win!',
            'live_view.branding' => 'Spin The Wheel',
        ]);
    }
}
