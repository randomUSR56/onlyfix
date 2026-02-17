<?php

/**
 * Development server launcher with platform-specific support.
 * This script detects the OS and runs concurrently with or without pail.
 */

$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$ssr = in_array('--ssr', $argv);

if ($ssr) {
    // SSR mode
    echo "Building SSR assets...\n";
    passthru('npm run build:ssr');
    
    if ($isWindows) {
        // Windows - without pail
        $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" '
            . '"php artisan serve" '
            . '"php artisan queue:listen --tries=1" '
            . '"php artisan inertia:start-ssr" '
            . '--names=server,queue,ssr --kill-others';
    } else {
        // Unix/Linux/Mac - with pail
        $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" '
            . '"php artisan serve" '
            . '"php artisan queue:listen --tries=1" '
            . '"php artisan pail --timeout=0" '
            . '"php artisan inertia:start-ssr" '
            . '--names=server,queue,logs,ssr --kill-others';
    }
} else {
    // Standard dev mode
    if ($isWindows) {
        // Windows - without pail
        $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fdba74" '
            . '"php artisan serve" '
            . '"php artisan queue:listen --tries=1" '
            . '"npm run dev" '
            . '--names=server,queue,vite --kill-others';
    } else {
        // Unix/Linux/Mac - with pail
        $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" '
            . '"php artisan serve" '
            . '"php artisan queue:listen --tries=1" '
            . '"php artisan pail --timeout=0" '
            . '"npm run dev" '
            . '--names=server,queue,logs,vite --kill-others';
    }
}

echo "Starting development server" . ($isWindows ? ' (Windows mode - pail disabled)' : '') . "...\n";
passthru($command, $exitCode);
exit($exitCode);
