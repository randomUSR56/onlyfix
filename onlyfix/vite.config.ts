import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { defineConfig } from 'vite';

const isDocker = process.env.DOCKER === '1';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        ...(!isDocker ? [wayfinder({ formVariants: true })] : []),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Vite asset URLs route through nginx (same origin as the page).
        // Nginx proxies /@vite/*, /resources/*, /node_modules/* to the
        // node container internally, avoiding broken WebSocket
        // port-forwarding in Docker Desktop for Windows.
        origin: isDocker ? process.env.APP_URL || 'http://onlyfix.local' : undefined,
        allowedHosts: isDocker ? true : undefined,
        // Disable HMR in Docker — Docker Desktop for Windows cannot
        // forward WebSocket connections, and Vite 7 blocks rendering
        // when the HMR handshake fails.  Not needed for production-
        // ready codebases; a full page reload after file changes is fine.
        hmr: isDocker ? false : undefined,
        watch: {
            usePolling: true,
            interval: 1000,
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    test: {
        environment: 'happy-dom',
        globals: true,
        setupFiles: ['resources/js/tests/setup.ts'],
        include: ['resources/js/tests/**/*.test.ts'],
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js'),
            },
        },
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html', 'json-summary'],
            include: [
                'resources/js/composables/**',
                'resources/js/components/**',
            ],
            exclude: [
                'resources/js/components/ui/**',
                'resources/js/tests/**',
            ],
        },
    },
});
