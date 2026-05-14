{{--
    Vite Assets Component
    ---------------------
    Resolves hashed asset paths directly from the compiled manifest.json,
    bypassing the @vite() helper entirely to avoid HMR/dev-server issues in
    production.  Falls back to the filenames committed alongside the manifest
    when the manifest cannot be read (e.g. a broken build).

    Usage:
        <x-vite-assets :assets="['resources/css/app.css', 'resources/js/app.js']" />
--}}
@php
    // Hardcoded fallback map — matches the manifest.json committed to the repo.
    // Update whenever `npm run build` produces new hashed filenames.
    $fallbackMap = [
        'resources/css/app.css'                     => '/build/assets/app-Crl__4MZ.css',
        'resources/css/login.css'                   => '/build/assets/login-DaaNGujz.css',
        'resources/css/views.css'                   => '/build/assets/views-BnuIr-_q.css',
        'resources/js/app.js'                       => '/build/assets/app-DrshNOhE.js',
        'resources/js/catalog-management-loader.js' => '/build/assets/catalog-management-loader-7r0mpOMY.js',
        'resources/js/messages-loader.js'           => '/build/assets/messages-loader-DkgPvLSU.js',
        'resources/js/notifications-loader.js'      => '/build/assets/notifications-loader-YwBQOkUC.js',
        'resources/js/profile-loader.js'            => '/build/assets/profile-loader-C1c0aron.js',
        'resources/js/user-management-loader.js'    => '/build/assets/user-management-loader-BoENbz92.js',
    ];

    // Try to read the real manifest so newly-built hashes are always used.
    $manifestPath = public_path('build/manifest.json');
    $resolvedMap  = $fallbackMap;

    if (file_exists($manifestPath)) {
        try {
            $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            foreach ($manifest as $src => $entry) {
                if (isset($entry['file'])) {
                    $resolvedMap[$src] = '/build/' . $entry['file'];
                }
            }
        } catch (\Throwable $e) {
            // Manifest unreadable — keep the hardcoded fallback map.
        }
    }
@endphp

@foreach ($assets as $asset)
    @if (isset($resolvedMap[$asset]))
        @if (str_ends_with($asset, '.css'))
            <link rel="stylesheet" href="{{ $resolvedMap[$asset] }}">
        @elseif (str_ends_with($asset, '.js'))
            <script type="module" src="{{ $resolvedMap[$asset] }}"></script>
        @endif
    @endif
@endforeach
