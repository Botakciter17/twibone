<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Storage::fake('public');
    }

    /**
     * Buat file PNG sementara dengan transparansi.
     */
    protected function createTransparentPng(int $w = 100, int $h = 100): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'twibbon_trans_') . '.png';
        $im = imagecreatetruecolor($w, $h);
        imagesavealpha($im, true);
        imagealphablending($im, false);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);
        imagepng($im, $tempPath);
        imagedestroy($im);

        return new UploadedFile(
            $tempPath,
            'frame.png',
            'image/png',
            null,
            true
        );
    }

    /**
     * Buat file PNG sementara solid tanpa transparansi (alpha = 0).
     */
    protected function createSolidPng(int $w = 100, int $h = 100): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'twibbon_solid_') . '.png';
        $im = imagecreatetruecolor($w, $h);
        $red = imagecolorallocate($im, 255, 0, 0);
        imagefill($im, 0, 0, $red);
        imagepng($im, $tempPath);
        imagedestroy($im);

        return new UploadedFile(
            $tempPath,
            'solid.png',
            'image/png',
            null,
            true
        );
    }

    public function test_guest_cannot_access_admin_campaigns(): void
    {
        $response = $this->get('/admin/campaigns');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/campaigns/create');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_campaigns_list(): void
    {
        Campaign::factory()->create([
            'title' => 'Twibbon Acara Kampus',
            'slug' => 'acara-kampus',
            'frame_path' => 'frames/test.png',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/campaigns');
        $response->assertStatus(200);
        $response->assertSee('Twibbon Acara Kampus');
    }

    public function test_admin_can_create_campaign_with_valid_transparent_png(): void
    {
        $file = $this->createTransparentPng(200, 200);

        $response = $this->actingAs($this->admin)->post('/admin/campaigns', [
            'title' => 'Dies Natalis 25',
            'slug' => 'dies-natalis-25',
            'description' => 'Rayakan ulang tahun ke-25',
            'caption' => 'Saya siap hadir!',
            'is_active' => true,
            'frame' => $file,
        ]);

        $response->assertRedirect(route('admin.campaigns.index'));
        $this->assertDatabaseHas('campaigns', [
            'title' => 'Dies Natalis 25',
            'slug' => 'dies-natalis-25',
            'caption' => 'Saya siap hadir!',
            'is_active' => true,
            'frame_width' => 200,
            'frame_height' => 200,
        ]);

        $campaign = Campaign::where('slug', 'dies-natalis-25')->first();
        $this->assertNotNull($campaign);
        Storage::disk('public')->assertExists($campaign->frame_path);
    }

    public function test_upload_rejected_if_not_png(): void
    {
        $fakeFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post('/admin/campaigns', [
            'title' => 'Test Gagal',
            'slug' => 'test-gagal',
            'frame' => $fakeFile,
        ]);

        $response->assertSessionHasErrors('frame');
        $this->assertDatabaseMissing('campaigns', ['slug' => 'test-gagal']);
    }

    public function test_upload_rejected_if_png_exceeds_2mb(): void
    {
        // File PNG palsu ukuran 3MB (3072 KB)
        $largeFile = UploadedFile::fake()->create('large.png', 3072, 'image/png');

        $response = $this->actingAs($this->admin)->post('/admin/campaigns', [
            'title' => 'Test Ukuran Besar',
            'slug' => 'test-besar',
            'frame' => $largeFile,
        ]);

        $response->assertSessionHasErrors('frame');
        $this->assertDatabaseMissing('campaigns', ['slug' => 'test-besar']);
    }

    public function test_upload_rejected_if_png_has_no_transparency(): void
    {
        $solidFile = $this->createSolidPng(150, 150);

        $response = $this->actingAs($this->admin)->post('/admin/campaigns', [
            'title' => 'Test Solid No Alpha',
            'slug' => 'test-solid',
            'frame' => $solidFile,
        ]);

        $response->assertSessionHasErrors('frame');
        $this->assertDatabaseMissing('campaigns', ['slug' => 'test-solid']);
    }

    public function test_admin_can_update_campaign_details(): void
    {
        $campaign = Campaign::create([
            'title' => 'Judul Awal',
            'slug' => 'judul-awal',
            'description' => 'Deskripsi lama',
            'caption' => 'Caption lama',
            'frame_path' => 'frames/test.png',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/campaigns/{$campaign->id}", [
            'title' => 'Judul Baru',
            'slug' => 'judul-awal', // Slug tetap jalan
            'description' => 'Deskripsi baru',
            'caption' => 'Caption baru',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('admin.campaigns.index'));
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'title' => 'Judul Baru',
            'description' => 'Deskripsi baru',
            'caption' => 'Caption baru',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_replace_png_without_changing_slug(): void
    {
        $oldFile = $this->createTransparentPng(100, 100);
        $oldPath = $oldFile->storeAs('frames', 'old.png', 'public');

        $campaign = Campaign::create([
            'title' => 'Twibbon Ganti PNG',
            'slug' => 'twibbon-ganti-png',
            'frame_path' => $oldPath,
            'frame_width' => 100,
            'frame_height' => 100,
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists($oldPath);

        $newFile = $this->createTransparentPng(300, 300);

        $response = $this->actingAs($this->admin)->put("/admin/campaigns/{$campaign->id}", [
            'title' => 'Twibbon Ganti PNG',
            'slug' => 'twibbon-ganti-png',
            'frame' => $newFile,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.campaigns.index'));

        $campaign->refresh();
        $this->assertNotEquals($oldPath, $campaign->frame_path);
        $this->assertEquals(300, $campaign->frame_width);
        $this->assertEquals(300, $campaign->frame_height);

        // File lama terhapus dan file baru tersimpan
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($campaign->frame_path);
    }

    public function test_admin_can_toggle_campaign_active_status(): void
    {
        $campaign = Campaign::create([
            'title' => 'Twibbon Status',
            'slug' => 'twibbon-status',
            'frame_path' => 'frames/test.png',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/campaigns/{$campaign->id}/toggle");
        $response->assertStatus(302);

        $campaign->refresh();
        $this->assertFalse($campaign->is_active);

        $this->actingAs($this->admin)->patch("/admin/campaigns/{$campaign->id}/toggle");
        $campaign->refresh();
        $this->assertTrue($campaign->is_active);
    }

    public function test_admin_can_delete_campaign(): void
    {
        $file = $this->createTransparentPng(100, 100);
        $path = $file->storeAs('frames', 'todelete.png', 'public');

        $campaign = Campaign::create([
            'title' => 'Twibbon Mau Dihapus',
            'slug' => 'twibbon-hapus',
            'frame_path' => $path,
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($this->admin)->delete("/admin/campaigns/{$campaign->id}");
        $response->assertRedirect(route('admin.campaigns.index'));

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
