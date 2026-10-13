<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * Tampilkan halaman editor twibbon untuk user publik.
     */
    public function show(string $slug): Response
    {
        $campaign = Campaign::query()
            ->where('slug', $slug)
            ->first();

        if (!$campaign || !$campaign->is_active) {
            abort(404, 'Twibbon tidak ditemukan atau sedang tidak aktif.');
        }

        return Inertia::render('Public/Editor', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'caption' => $campaign->caption,
                'frame_url' => $campaign->frame_url,
                'frame_width' => $campaign->frame_width,
                'frame_height' => $campaign->frame_height,
            ],
        ]);
    }
}
