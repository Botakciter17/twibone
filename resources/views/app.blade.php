<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $campaign = $page['props']['campaign'] ?? null;
            $rawTitle = $campaign['title'] ?? null;
            
            if ($rawTitle) {
                $ogTitle = 'Yuk Pasang Twibbon ' . ucwords($rawTitle) . ' ✨';
                $ogDesc = !empty($campaign['description']) 
                    ? $campaign['description'] 
                    : 'Yuk pasang foto terbaikmu dan dukung ' . ucwords($rawTitle) . '! Pasang twibbon praktis langsung dari browser sekarang.';
            } else {
                $ogTitle = config('app.name', 'Twibbon Online') . ' — Pasang Bingkai Foto Praktis';
                $ogDesc = 'Yuk pasang dan bagikan twibbon foto kamu secara mudah, cepat, dan praktis langsung dari browser!';
            }

            $ogImage = (!empty($campaign['frame_url'])) ? url($campaign['frame_url']) : null;
        @endphp

        <title inertia>{{ $ogTitle }}</title>
        <meta name="description" content="{{ $ogDesc }}">

        <!-- Open Graph / Facebook / WhatsApp -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDesc }}">
        <meta property="og:site_name" content="Twibbon Online">
        @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:secure_url" content="{{ $ogImage }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="{{ $campaign['frame_width'] ?? 1080 }}">
        <meta property="og:image:height" content="{{ $campaign['frame_height'] ?? 1080 }}">
        @endif

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url()->current() }}">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDesc }}">
        @if($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
