<?php

namespace Tests\Feature;

use App\Livewire\Admin\FrontViewBanner;
use App\Models\Campaign;
use App\Models\Prize;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FrontViewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_front_view_is_publicly_accessible_with_the_shared_wheel_layout(): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();

        $response = $this->get(route('front-view'));

        $response->assertOk();
        $response->assertSee('id="live-content"', false);
        $response->assertSee('id="idle-slideshow"', false);
        $response->assertSee('id="wheel-stage"', false);
        $response->assertSee('id="queue-list"', false);
        $response->assertSee('id="prize-reveal"', false);
        $response->assertSee('manifest-front-view.json', false);
    }

    public function test_front_view_slideshow_is_empty_when_disabled_even_with_images_stored(): void
    {
        Settings::setMany([
            'front_view.enabled' => false,
            'front_view.images' => ['front-view/one.jpg'],
        ]);

        $response = $this->get(route('front-view'));

        $response->assertOk();
        $response->assertDontSee('front-view/one.jpg', false);
    }

    public function test_front_view_slideshow_shows_uploaded_images_when_enabled(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('front-view/one.jpg', 'fake');

        Settings::setMany([
            'front_view.enabled' => true,
            'front_view.images' => ['front-view/one.jpg'],
        ]);

        $response = $this->get(route('front-view'));

        $response->assertOk();
        $response->assertSee('front-view/one.jpg', false);
    }

    public function test_admin_can_save_front_view_settings(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(FrontViewBanner::class)
            ->set('interval_seconds', 10)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(10, Settings::get('front_view.interval_seconds'));
    }

    public function test_admin_cannot_enable_banner_without_at_least_one_image(): void
    {
        $this->actingAs($this->admin(), 'web');

        Livewire::test(FrontViewBanner::class)
            ->set('enabled', true)
            ->call('save')
            ->assertHasErrors(['enabled']);

        $this->assertFalse(Settings::get('front_view.enabled'));
    }

    public function test_admin_can_upload_and_remove_slideshow_images(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(), 'web');

        $component = Livewire::test(FrontViewBanner::class)
            ->set('newImage', UploadedFile::fake()->image('banner.jpg'))
            ->call('addImage')
            ->assertHasNoErrors();

        $stored = Settings::get('front_view.images');
        $this->assertCount(1, $stored);
        Storage::disk('public')->assertExists($stored[0]);

        $component->call('removeImage', 0);

        $this->assertCount(0, Settings::get('front_view.images'));
        Storage::disk('public')->assertMissing($stored[0]);
    }

    public function test_admin_cannot_exceed_the_max_image_cap(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin(), 'web');

        Settings::set('front_view.images', array_fill(0, FrontViewBanner::MAX_IMAGES, 'front-view/placeholder.jpg'));

        Livewire::test(FrontViewBanner::class)
            ->set('newImage', UploadedFile::fake()->image('one-too-many.jpg'))
            ->call('addImage')
            ->assertHasErrors(['newImage']);

        $this->assertCount(FrontViewBanner::MAX_IMAGES, Settings::get('front_view.images'));
    }

    public function test_admin_can_reorder_slideshow_images(): void
    {
        Settings::set('front_view.images', ['a.jpg', 'b.jpg', 'c.jpg']);
        $this->actingAs($this->admin(), 'web');

        Livewire::test(FrontViewBanner::class)->call('moveDown', 0);

        $this->assertSame(['b.jpg', 'a.jpg', 'c.jpg'], Settings::get('front_view.images'));
    }

    public function test_front_view_admin_page_requires_admin_access(): void
    {
        $staff = User::factory()->create(['is_admin' => false, 'is_staff' => true]);
        $this->actingAs($staff, 'web');

        $this->get(route('admin.front-view'))->assertForbidden();
    }
}
