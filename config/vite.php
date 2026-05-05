<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vite Build Directory
    |--------------------------------------------------------------------------
    |
    | This value determines the directory where Vite's compiled assets are
    | placed during a production build. Laravel's @vite() Blade directive
    | reads the manifest from this directory to resolve asset URLs.
    |
    */

    'build_directory' => 'build',

    /*
    |--------------------------------------------------------------------------
    | Vite Manifest Path
    |--------------------------------------------------------------------------
    |
    | This is the full path to the Vite manifest file generated during the
    | production build. Laravel uses this manifest to map source asset
    | paths to their hashed, compiled counterparts in public/build/.
    |
    */

    'manifest_path' => 'public/build/manifest.json',

];
