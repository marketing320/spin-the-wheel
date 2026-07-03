<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\GeofenceLog;
use App\Models\Player;

/**
 * Server-side location validation. Never trusts the frontend for the pass/fail
 * decision — the distance is always recomputed here with the Haversine formula
 * and every check is logged for audit (privately).
 */
class GeofenceService
{
    /**
     * @return array{passed: bool, distance: ?float, reason: ?string, enabled: bool, message: ?string}
     */
    public function check(Campaign $campaign, ?float $lat, ?float $lng, ?Player $player = null): array
    {
        $setting = $campaign->geofenceSetting;

        // No geofence configured or disabled → always pass.
        if (! $setting || ! $setting->enabled || ! $setting->isConfigured()) {
            return [
                'passed' => true,
                'distance' => null,
                'reason' => 'geofence_disabled',
                'enabled' => false,
                'message' => null,
            ];
        }

        $blockedMessage = $setting->blocked_message
            ?: 'You must be at the event location to spin the wheel.';

        // Location unavailable (permission denied / unsupported).
        if ($lat === null || $lng === null) {
            $this->log($player, $campaign, null, null, null, false, 'location_unavailable');

            return [
                'passed' => false,
                'distance' => null,
                'reason' => 'location_unavailable',
                'enabled' => true,
                'message' => $blockedMessage,
            ];
        }

        $distance = self::haversine(
            (float) $setting->latitude,
            (float) $setting->longitude,
            $lat,
            $lng
        );

        $passed = $distance <= $setting->radius_meters;
        $reason = $passed ? 'inside_radius' : 'outside_radius';

        $this->log($player, $campaign, $lat, $lng, $distance, $passed, $reason);

        return [
            'passed' => $passed,
            'distance' => round($distance, 2),
            'reason' => $reason,
            'enabled' => true,
            'message' => $passed ? null : $blockedMessage,
        ];
    }

    protected function log(
        ?Player $player,
        Campaign $campaign,
        ?float $lat,
        ?float $lng,
        ?float $distance,
        bool $passed,
        string $reason
    ): void {
        GeofenceLog::create([
            'player_id' => $player?->id,
            'campaign_id' => $campaign->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'distance_meters' => $distance,
            'passed' => $passed,
            'reason' => $reason,
        ]);
    }

    /**
     * Great-circle distance between two coordinates, in metres.
     */
    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6_371_000; // metres

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
