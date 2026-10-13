<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Admin
        User::updateOrCreate(
            ['email' => 'admin@twibbon.local'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Rudalcabe123#'),
            ]
        );

        // 2. Contoh Campaign Twibbon
        Campaign::updateOrCreate(
            ['slug' => 'dies-natalis-2026'],
            [
                'title' => 'Dies Natalis Kampus 2026',
                'description' => "Selamat datang di generator twibbon resmi Dies Natalis Kampus 2026!\nUnggah foto terbaikmu, atur zoom dan posisi agar pas di dalam bingkai, lalu unduh hasilnya dan salin caption untuk posting di media sosial.",
                'frame_path' => 'frames/sample-twibbon.png',
                'frame_width' => 1080,
                'frame_height' => 1080,
                'caption' => "Halo semua! 🎓✨\nSaya bangga menjadi bagian dari perayaan Dies Natalis Kampus 2026.\nMari bersama-sama melangkah maju menuju masa depan gemilang!\n\nYuk pasang twibbon kamu juga!\n#DiesNatalis2026 #KampusHebat #TwibbonResmi",
                'is_active' => true,
            ]
        );
    }
}
