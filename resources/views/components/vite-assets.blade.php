{{--
    Vite Assets Component
    ---------------------
    Wraps @vite() with a direct-file fallback in case manifest resolution fails.

    Usage:
        <x-vite-assets :assets="['resources/css/app.css', 'resources/js/app.js']" />

    Behaviour:
        - When public/build/manifest.json exists, delegates to @vite() so that
          hashed filenames, HMR, and module preloading all work normally.
        - When the manifest is missing (e.g. first deploy before build runs),
          it falls back to serving the files directly from /build/assets/ using
          the filenames recorded in the manifest that ships with the repo, so
          the page is never left completely unstyled.
--}}
@php
    $manifestPath = public_path('build/manifest.json');
    $useVite      = file_exists($manifestPath);

    if (! $useVite) {
        // Fallback map derived from the committed manifest.json.
        // Update these whenever `npm run build` produces new hashed filenames.
        $fallbackMap = [
            'resources/css/app.css'                        => '/build/assets/app-Crl__4MZ.css',
            'resources/css/login.css'                      => '/build/assets/login-DaaNGujz.css',
            'resources/css/views.css'                      => '/build/assets/views-BnuIr-_q.css',
            'resources/js/app.js'                          => '/build/assets/app-DrshNOhE.js',
            'resources/js/catalog-management-loader.js'    => '/build/assets/catalog-management-loader-7r0mpOMY.js',
            'resources/js/messages-loader.js'              => '/build/assets/messages-loader-DkgPvLSU.js',
            'resources/js/notifications-loader.js'         => '/build/assets/notifications-loader-YwBQOkUC.js',
            'resources/js/profile-loader.js'               => '/build/assets/profile-loader-C1c0aron.js',
            'resources/js/user-management-loader.js'       => '/build/assets/user-management-loader-BoENbz92.js',
        ];
    }
@endphp

@if ($useVite)
    @vite($assets)
@else
    @foreach ($assets as $asset)
        @if (isset($fallbackMap[$asset]))
            @if (str_ends_with($asset, '.css'))
                <link rel="stylesheet" href="{{ $fallbackMap[$asset] }}">
            @elseif (str_ends_with($asset, '.js'))
                <script type="module" src="{{ $fallbackMap[$asset] }}"></script>
            @endif
        @endif
    @endforeach
@endif
