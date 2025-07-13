import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/app.css'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'vue': 'vue/dist/vue.esm-bundler.js'
        },
        extensions: ['.js', '.vue', '.json']
    },
    server: {
        host: '0.0.0.0',
        port: 5176,
        hmr: {
            host: 'localhost',
            protocol: 'ws'
        },
        watch: {
            usePolling: true
        }
    },
    optimizeDeps: {
        include: [
            'vue',
            'vue-i18n',
            'lucide-vue-next',
            'vue3-recharts'
        ],
        exclude: []
    }
});