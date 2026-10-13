<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Twibbon Tidak Ditemukan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Figtree, system-ui, -apple-system, sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; }
        .card { max-width: 420px; width: 100%; background: #ffffff; padding: 2.5rem 2rem; border-radius: 1.25rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .badge { display: inline-flex; align-items: center; justify-content: center; width: 4rem; height: 4rem; border-radius: 9999px; background: #f1f5f9; color: #0f172a; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.25rem; border: 1px solid #e2e8f0; }
        h1 { font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; color: #0f172a; }
        p { font-size: 0.875rem; color: #64748b; line-height: 1.5; margin-bottom: 1.75rem; }
        a { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0.625rem 1.5rem; font-size: 0.875rem; font-weight: 600; color: #ffffff; background: #0f172a; border-radius: 0.75rem; text-decoration: none; transition: background 0.15s; }
        a:hover { background: #1e293b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">404</div>
        <h1>Twibbon Tidak Ditemukan</h1>
        <p>{{ $exception->getMessage() ?: 'Halaman twibbon ini mungkin sudah dinonaktifkan, dihapus oleh admin, atau alamat link salah.' }}</p>
        <a href="/">Kembali ke Beranda</a>
    </div>
</body>
</html>
