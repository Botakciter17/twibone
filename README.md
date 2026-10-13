# Web Twibbon Self-Host

Aplikasi web twibbon mandiri (self-host) berbasis Laravel 12 + Vue 3 (Inertia.js) + Tailwind CSS sesuai spesifikasi [PRD.md](PRD.md).

## Fitur Utama

### 1. Sisi Pengguna Publik (Mobile-First Editor)
- **URL Ramah Publik (`/{slug}`)**: Buka editor langsung tanpa login atau daftar akun.
- **Client-Side Canvas Processing**: Foto user **100% diproses di browser/perangkat pengguna** dan **tidak pernah dikirim ke server** (menjamin privasi & performa tinggi).
- **Format Foto Fleksibel**: Mendukung JPG, PNG, WebP (dari galeri maupun kamera HP).
- **Pengaturan Posisi Lengkap**:
  - Geser (drag pan) dengan mouse atau 1-finger touch.
  - Perbesar / perkecil (zoom slider, pinch gesture 2 jari, tombol `+`/`-`).
  - Putar foto (slider derajat -180° s.d. +180° dan tombol cepat `+90°`).
  - Reset posisi dan ganti foto tanpa perlu memuat ulang halaman.
- **Ekspor Berkualitas Tinggi**: Hasil unduhan PNG memiliki resolusi yang sama persis dengan file twibbon asli admin (`twibbon-{slug}.png`).
- **Salin Caption 1-Klik**: Tombol salin caption bawaan admin ke clipboard dengan notifikasi "Tersalin!", dilengkapi fallback jika browser membatasi clipboard API.
- **Penanganan Edge Case**:
  - Koreksi orientasi EXIF otomatis via `createImageBitmap` agar foto HP tidak terbalik.
  - Fallback panduan tekan-lama untuk Safari iOS jika unduhan otomatis dibatasi.
  - Peringatan ramah bila resolusi foto user terlalu rendah.

### 2. Sisi Admin
- **Autentikasi Aman**: Login admin email + password dengan sesi CSRF (`/admin/login`).
- **Manajemen Twibbon (CRUD)**:
  - Buat twibbon baru dengan validasi ketat.
  - Auto-generate slug dari judul dengan opsi penyesuaian manual.
  - Ubah informasi twibbon, caption, atau ganti file PNG tanpa mengubah slug link publik.
  - Toggle aktif / nonaktifkan twibbon secara instan.
  - Hapus twibbon dengan konfirmasi modal.
  - Tombol 1-klik salin tautan publik (`/{slug}`) dan pratinjau langsung.
- **Validasi File Server-Side**:
  - Hanya menerima format PNG dengan ukuran maksimal 2 MB.
  - Wajib memiliki **alpha channel / transparansi** (dicek lewat header PNG & piksel GD).
  - Nama file diacak (randomized) demi keamanan.
  - Preview transparansi dengan pola kotak-kotak (checkerboard).

---

## Akun Admin Bawaan (Seeder)

- **URL Login Admin**: `/admin/login` atau `/login`
- **Email**: `admin@twibbon.local`
- **Password**: `password`

---

## Cara Menjalankan

### 1. Persiapan Awal
```bash
# Salin file environment jika belum ada
cp .env.example .env

# Generate app key (sudah ter-generate)
php artisan key:generate

# Migrasi database SQLite & isi data awal (seeder)
php artisan migrate --seed

# Buat tautan storage publik
php artisan storage:link
```

### 2. Kompilasi Aset Frontend
```bash
# Build untuk produksi
npm run build

# Atau jalankan dev server Vite untuk pengembangan
npm run dev
```

### 3. Jalankan Server Web
```bash
php artisan serve
```
Akses di browser:
- Halaman Publik Contoh: `http://127.0.0.1:8000/dies-natalis-2026`
- Halaman Login Admin: `http://127.0.0.1:8000/admin/login`

---

## Menjalankan Pengujian (Testing)

```bash
php artisan test
```
Semua 36 skenario pengujian (otentikasi, validasi PNG transparan, CRUD admin, dan halaman publik) berjalan lulus 100%.
