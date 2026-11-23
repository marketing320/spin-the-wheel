<?php

namespace Tests\Feature;

use Tests\TestCase;

class RetiredDisplayRoutesTest extends TestCase
{
    public function test_removed_display_routes_return_not_found(): void
    {
        foreach ([
            '/front-view', '/front-view/active-spin', '/live-view', '/live-view/leaderboard',
            '/live-view/active-spin', '/roadshow-live', '/admin/live-view',
        ] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->postJson('/spin/api/queue')->assertNotFound();
    }
}
