<?php

namespace Tests\Feature;

use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_access_active_campaign_editor(): void
    {
        $campaign = Campaign::create([
            'title' => 'Festival Kampus 2026',
            'slug' => 'festival-kampus-2026',
            'description' => 'Ikuti keseruan festival kampus!',
            'caption' => 'Saya hadir di festival ini! #FestivalKampus2026',
            'frame_path' => 'frames/festival.png',
            'frame_width' => 1080,
            'frame_height' => 1080,
            'is_active' => true,
        ]);

        $response = $this->get('/festival-kampus-2026');

        $response->assertStatus(200);
        $response->assertSee('Festival Kampus 2026');
        $response->assertSee('Saya hadir di festival ini! #FestivalKampus2026');
    }

    public function test_campaign_with_caption_generator_placeholders_renders(): void
    {
        Campaign::create([
            'title' => 'LDK 2026',
            'slug' => 'ldk-2026',
            'description' => 'Latihan Dasar Kepemimpinan',
            'caption' => 'Saya {Nama Lengkap} dari {Nama Organisasi} siap mengikuti LDK 2026!',
            'frame_path' => 'frames/ldk.png',
            'frame_width' => 1080,
            'frame_height' => 1080,
            'is_active' => true,
        ]);

        $response = $this->get('/ldk-2026');

        $response->assertStatus(200);
        $response->assertSee('LDK 2026');
        $response->assertSee('{Nama Lengkap}');
        $response->assertSee('{Nama Organisasi}');
    }

    public function test_inactive_campaign_returns_404(): void
    {
        Campaign::create([
            'title' => 'Acara Sudah Lewat',
            'slug' => 'acara-lewat',
            'frame_path' => 'frames/old.png',
            'is_active' => false,
        ]);

        $response = $this->get('/acara-lewat');

        $response->assertStatus(404);
    }

    public function test_non_existent_slug_returns_404(): void
    {
        $response = $this->get('/slug-yang-tidak-pernah-ada');

        $response->assertStatus(404);
    }
}
