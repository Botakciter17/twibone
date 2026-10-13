<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Rules\ValidPngWithAlpha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * Tampilkan daftar twibbon.
     */
    public function index(): Response
    {
        $campaigns = Campaign::query()
            ->latest()
            ->get();

        return Inertia::render('Admin/Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Form buat twibbon baru.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Campaigns/Create');
    }

    /**
     * Simpan twibbon baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $pngRule = new ValidPngWithAlpha();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:campaigns,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'caption' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['boolean'],
            'frame' => ['required', 'file', $pngRule],
        ]);

        $file = $request->file('frame');
        // Acak nama file demi keamanan
        $fileName = Str::random(40) . '.png';
        $path = $file->storeAs('frames', $fileName, 'public');

        Campaign::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'frame_path' => $path,
            'frame_width' => $pngRule->getWidth(),
            'frame_height' => $pngRule->getHeight(),
        ]);

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Twibbon berhasil dibuat.');
    }

    /**
     * Form edit twibbon.
     */
    public function edit(Campaign $campaign): Response
    {
        return Inertia::render('Admin/Campaigns/Edit', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Update twibbon.
     */
    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $pngRule = new ValidPngWithAlpha();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('campaigns', 'slug')->ignore($campaign->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'caption' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['boolean'],
            'frame' => ['nullable', 'file', $pngRule],
        ];

        $validated = $request->validate($rules);

        $data = [
            'title' => $validated['title'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('frame')) {
            // Hapus file lama jika ada
            if ($campaign->frame_path && Storage::disk('public')->exists($campaign->frame_path)) {
                Storage::disk('public')->delete($campaign->frame_path);
            }

            $file = $request->file('frame');
            $fileName = Str::random(40) . '.png';
            $path = $file->storeAs('frames', $fileName, 'public');

            $data['frame_path'] = $path;
            $data['frame_width'] = $pngRule->getWidth();
            $data['frame_height'] = $pngRule->getHeight();
        }

        $campaign->update($data);

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Twibbon berhasil diperbarui.');
    }

    /**
     * Toggle status aktif/nonaktif twibbon.
     */
    public function toggle(Campaign $campaign): RedirectResponse
    {
        $campaign->update([
            'is_active' => !$campaign->is_active,
        ]);

        $status = $campaign->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Twibbon berhasil {$status}.");
    }

    /**
     * Hapus twibbon.
     */
    public function destroy(Campaign $campaign): RedirectResponse
    {
        if ($campaign->frame_path && Storage::disk('public')->exists($campaign->frame_path)) {
            Storage::disk('public')->delete($campaign->frame_path);
        }

        $campaign->delete();

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Twibbon berhasil dihapus.');
    }
}
