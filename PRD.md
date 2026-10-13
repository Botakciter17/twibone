# PRD — Web Twibbon Self-Host

Tanggal: 2026-10-13

## 1. Ringkasan

Web twibbon self-host: admin mengunggah bingkai PNG transparan beserta caption, lalu pengunjung tinggal mengunggah foto, foto otomatis diletakkan di bawah layer twibbon, hasilnya diunduh, dan caption disalin dengan satu klik.

**Masalah:** layanan twibbon publik penuh iklan, membatasi fitur, dan kita tidak mengontrol data maupun tampilannya. Repo open source yang ada rata-rata hanya satu frame yang di-hardcode, tanpa halaman admin.

**Pengguna utama:** panitia atau admin organisasi yang membuat twibbon untuk sebuah acara, dan peserta atau pengunjung yang memakainya, mayoritas lewat HP.

## 2. Tujuan dan non-tujuan

Versi 1 fokus pada satu alur: admin siapkan twibbon, user membuat dan mengunduh fotonya dalam kurang dari satu menit.

**Tujuan**

- Admin bisa mengunggah PNG twibbon dan mengisi caption lewat halaman admin.
- User bisa mengunggah foto, mengatur posisinya, lalu mengunduh hasil tanpa daftar atau login.
- Foto user berada di bawah layer twibbon, sehingga bingkai selalu menutupi tepi foto.
- User bisa menyalin caption yang sudah diisi admin dengan satu klik.
- Bisa di-host sendiri (VPS atau shared hosting), tanpa layanan pihak ketiga.

**Non-tujuan versi 1**

- Akun user, galeri hasil, atau penyimpanan foto user di server.
- Filter, stiker, teks, atau edit lanjutan selain geser, zoom, dan putar.
- Moderasi, komentar, atau fitur sosial.
- Statistik lanjutan (counter pemakai bisa jadi tambahan setelah versi 1).

## 3. Peran pengguna

Ada dua peran: Admin yang mengelola twibbon, dan User publik yang memakainya tanpa akun.

| Peran | Login | Yang boleh dilakukan |
| --- | --- | --- |
| Admin | Ya (email + password) | Membuat, mengubah, menonaktifkan, dan menghapus twibbon; mengunggah PNG; mengisi dan mengubah caption |
| User publik | Tidak | Membuka halaman twibbon, mengunggah foto sendiri, mengatur posisi, mengunduh hasil, menyalin caption |

## 4. Alur pengguna

Dua alur lurus, masing-masing lima langkah: admin menyiapkan twibbon sekali, user memakainya berkali-kali.

**Alur admin**

1. Login ke halaman admin.
2. Buat twibbon baru: isi judul, deskripsi singkat, dan slug (alamat link).
3. Unggah PNG twibbon transparan; sistem memvalidasi dan menampilkan preview.
4. Isi caption bawaan yang nanti disalin user.
5. Simpan dan aktifkan; link publik `/{slug}` siap dibagikan.

**Alur user**

1. Buka link twibbon dari admin atau dari media sosial.
2. Unggah foto dari galeri atau kamera HP.
3. Geser, zoom, dan putar foto sampai pas di dalam bingkai.
4. Klik Download untuk menyimpan hasil sebagai gambar.
5. Klik Salin Caption, lalu tempel saat memposting.

## 5. Kebutuhan halaman admin

Admin harus bisa mengelola twibbon dan caption tanpa menyentuh kode atau server.

| ID | Kebutuhan | Detail |
| --- | --- | --- |
| A1 | Login admin | Email + password; sesi dengan CSRF protection; akun admin dibuat lewat seeder |
| A2 | Daftar twibbon | Tabel berisi judul, slug, status aktif, tanggal dibuat, dan tombol edit, nonaktifkan, hapus |
| A3 | Buat dan edit twibbon | Field: judul, slug unik, deskripsi, file PNG, caption, status aktif |
| A4 | Upload PNG | Hanya PNG, maksimal 2 MB, disarankan persegi (mis. 1080 × 1080 px); ditolak jika bukan PNG atau tidak punya kanal alpha |
| A5 | Preview | Setelah upload, tampil preview bingkai di atas latar kotak-kotak (checkerboard) agar area transparan terlihat |
| A6 | Caption | Textarea multi-baris; teks disimpan apa adanya dan boleh kosong |
| A7 | Ganti PNG | Mengganti file tanpa mengubah slug, sehingga link yang sudah dibagikan tetap jalan |
| A8 | Salin link | Tombol untuk menyalin link publik `/{slug}` |

## 6. Kebutuhan halaman user

Halaman user adalah satu layar editor: foto digambar lebih dulu di canvas, lalu PNG twibbon ditumpuk di atasnya, sehingga bingkai selalu menutupi tepi foto.

| ID | Kebutuhan | Detail |
| --- | --- | --- |
| U1 | Halaman publik | `/{slug}` menampilkan judul, deskripsi, dan editor; slug tidak aktif atau tidak ada menampilkan halaman 404 |
| U2 | Upload foto | JPG, PNG, atau WebP, maksimal 10 MB; bisa dari galeri maupun kamera HP |
| U3 | Urutan layer | Latar kosong, foto user di tengah, PNG twibbon di paling atas |
| U4 | Atur posisi | Geser (drag), zoom (pinch atau slider), dan putar; foto awalnya otomatis mengisi penuh area canvas |
| U5 | Download | Ekspor PNG dengan resolusi yang sama dengan file twibbon; nama file `twibbon-{slug}.png` |
| U6 | Salin caption | Tombol menyalin caption dari admin ke clipboard dan memberi konfirmasi "Tersalin"; tombol disembunyikan jika caption kosong |
| U7 | Ganti foto | User bisa mengganti foto tanpa memuat ulang halaman |
| U8 | Pemrosesan di browser | Foto tidak dikirim ke server; seluruh penggabungan dilakukan di canvas pada perangkat user |

## 7. Kebutuhan non-fungsional

Prioritasnya HP: mayoritas user membuka link dari media sosial di browser mobile.

- **Mobile first:** layout responsif, dipakai nyaman di layar 360 px; tombol minimal 44 px agar mudah ditekan.
- **Performa:** halaman publik termuat di bawah 2 detik di 4G; foto besar diperkecil sebelum digambar ke canvas agar tidak berat di HP kelas bawah.
- **Privasi:** foto user tidak pernah meninggalkan perangkatnya.
- **Keamanan:** validasi tipe file di server (bukan hanya ekstensi), nama file diacak, rate limit pada login, CSRF aktif, dan caption ditampilkan sebagai teks biasa (bukan HTML) untuk mencegah XSS.
- **Canvas tidak "tainted":** file PNG twibbon disajikan dari domain yang sama dengan aplikasi, atau dengan header CORS yang benar, agar `canvas.toBlob()` bisa mengekspor hasil.
- **Clipboard:** salin caption memakai Clipboard API yang butuh HTTPS; sediakan fallback seleksi teks jika gagal.
- **Orientasi foto:** foto dari HP dibaca dengan memperhatikan data EXIF, agar tidak muncul terbalik atau miring.
- **Browser target:** Chrome dan Safari (Android dan iOS) dua versi terakhir.

## 8. Model data dan rute

Cukup dua tabel: `users` untuk admin dan `campaigns` untuk twibbon; foto user tidak disimpan sama sekali.

**Tabel `campaigns`**

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint | Primary key |
| title | string | Judul twibbon |
| slug | string, unik | Dipakai di link publik |
| description | text, nullable | Deskripsi singkat di halaman publik |
| frame_path | string | Lokasi file PNG di storage |
| caption | text, nullable | Caption yang disalin user |
| is_active | boolean | Hanya yang aktif bisa dibuka publik |
| created_at, updated_at | timestamp | Otomatis |

**Rute**

| Rute | Akses | Fungsi |
| --- | --- | --- |
| `/{slug}` | Publik | Editor twibbon untuk user |
| `/admin/login` | Publik | Form login admin |
| `/admin/campaigns` | Admin | Daftar twibbon |
| `/admin/campaigns/create` | Admin | Form buat twibbon |
| `/admin/campaigns/{id}/edit` | Admin | Form edit twibbon, ganti PNG, ubah caption |

## 9. Stack teknis

Rekomendasi: Laravel + Vue.js, karena kebutuhan backend kecil (CRUD, auth, upload) dan sisi berat ada di canvas browser.

| Lapisan | Pilihan | Alasan |
| --- | --- | --- |
| Backend | Laravel (Breeze untuk auth admin) | Auth, validasi, dan upload file sudah tersedia |
| Frontend | Vue 3 (via Inertia atau SPA) | Editor canvas berupa satu komponen |
| Canvas | Canvas API murni, atau Konva.js bila ingin drag, zoom, dan rotate lebih cepat jadi | Penggabungan foto dan PNG dilakukan di browser |
| Database | SQLite atau MySQL | Hanya dua tabel, SQLite sudah cukup untuk awal |
| Storage | Disk `public` Laravel | File PNG disajikan dari domain yang sama |
| Deploy | Docker atau VPS dengan Nginx + HTTPS | Mudah di-self-host; HTTPS wajib untuk Clipboard API |

Alternatif yang lebih ringan: Next.js + SQLite, dengan konsep dan fitur yang sama.

## 10. Kriteria penerimaan, risiko, dan milestone

Versi 1 dianggap selesai jika semua kriteria di bawah terpenuhi di HP Android dan iOS.

**Kriteria penerimaan**

- [ ] Admin bisa login, membuat twibbon, mengunggah PNG, dan mengisi caption.
- [ ] Upload ditolak jika file bukan PNG, lebih dari 2 MB, atau tanpa transparansi.
- [ ] Halaman `/{slug}` terbuka tanpa login dan menampilkan editor.
- [ ] Foto user tampil di bawah layer twibbon dan bisa digeser, di-zoom, dan diputar.
- [ ] Tombol Download menghasilkan PNG yang sama persis dengan preview.
- [ ] Tombol Salin Caption menyalin teks dari admin dan memberi konfirmasi.
- [ ] Foto user tidak terkirim ke server (terbukti di tab Network browser).

**Edge case dan risiko**

| Kasus | Penanganan |
| --- | --- |
| Foto beresolusi sangat rendah | Tampilkan peringatan bahwa hasil bisa pecah |
| Foto dari HP terbalik karena EXIF | Baca orientasi EXIF sebelum digambar |
| Safari iOS tidak mengunduh otomatis | Tampilkan gambar hasil dengan petunjuk tekan lama untuk simpan |
| Clipboard API gagal (non-HTTPS) | Fallback: teks caption dipilih otomatis agar bisa disalin manual |
| PNG twibbon terlalu besar | Batas 2 MB dan peringatan ukuran yang disarankan |
| Slug nonaktif atau dihapus | Halaman 404 yang ramah |

**Milestone**

1. Prototipe editor canvas statis dengan satu frame: geser, zoom, putar, download.
2. Backend Laravel: auth admin, CRUD twibbon, upload dan validasi PNG.
3. Halaman publik `/{slug}` terhubung ke data, ditambah tombol Salin Caption.
4. Deploy dengan HTTPS, uji di HP Android dan iOS, perbaikan akhir.

**Pertanyaan terbuka**

- Apakah cukup satu twibbon aktif, atau admin perlu membuat banyak twibbon sekaligus (rancangan di atas mendukung banyak)?
- Apakah admin lebih dari satu orang?