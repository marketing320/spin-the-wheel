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
            ['name' => 'Administrator', 'password' => Hash::make('password'), 'is_admin' => true, 'is_staff' => false]
        );

        User::updateOrCreate(
            ['email' => 'marketing@brightstarcomp.com'],
            ['name' => 'Brightstar Marketing', 'password' => Hash::make('password'), 'is_admin' => true, 'is_staff' => false]
        );

        // Cashier account: dashboard and spin history.
        User::updateOrCreate(
            ['email' => 'cashier@brightstarcomp.com'],
            ['name' => 'Event Cashier', 'password' => Hash::make('password'), 'is_admin' => false, 'is_staff' => true]
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
        // Columns: name, rarity, color, wheel slots, confetti, stock limit.
        $prizes = [
            ['RM50 Voucher', 'common', '#0e75bc', 15, 'light', null],
            ['RM70 Voucher', 'rare', '#12a5b0', 9, 'strong', null],
            ['RM100 Voucher', 'legendary', '#f6c31c', 3, 'max', 3],
        ];

        foreach ($prizes as $i => [$name, $rarity, $color, $slots, $confetti, $stock]) {
            Prize::firstOrCreate(
                ['campaign_id' => $campaign->id, 'name' => $name],
                [
                    'rarity' => $rarity,
                    'type' => Prize::TYPE_VOUCHER,
                    'color' => $color,
                    'segment_count' => $slots,
                    'weight' => $slots,
                    'win_percentage' => round($slots * 100 / 27, 4),
                    'inventory_enabled' => $stock !== null,
                    'inventory_quantity' => $stock,
                    'confetti_level' => $confetti,
                    'redemption_message' => 'Show your voucher code or QR at the counter.',
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
            'branding.tagline' => "Create, game, and explore what's next with GeForce and Spark.",
        ]);
    }
}
