<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script async src="https://www.googletagmanager.com/gtm.js?id=GTM-TQ6MKRZ2"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
        </script>

        <script>
            (() => {
                let preference = 'system';
                try { preference = localStorage.getItem('appearance') || 'system'; } catch {}
                const dark = preference === 'dark' || (preference !== 'light' && preference !== 'dark' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            })();
        </script>
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        @if (!empty($ogTitle))
            <meta property="og:type" content="article">
            <meta property="og:site_name" content="Carverse KE">
            <meta property="og:title" content="{{ $ogTitle }}">
            <meta property="og:description" content="{{ $ogDescription }}">
            <meta property="og:url" content="{{ $ogUrl }}">
            @if (!empty($ogImage))
                <meta property="og:image" content="{{ $ogImage }}">
                <meta property="og:image:alt" content="{{ $ogTitle }}">
                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:image" content="{{ $ogImage }}">
            @else
                <meta name="twitter:card" content="summary">
            @endif
            <meta name="twitter:title" content="{{ $ogTitle }}">
            <meta name="twitter:description" content="{{ $ogDescription }}">
        @endif

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="apple-touch-icon" href="/favicon.svg">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
