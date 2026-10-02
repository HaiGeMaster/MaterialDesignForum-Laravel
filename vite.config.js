import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

// 构建时间戳：dev 模式取当前时间，build 模式取打包时间
const BUILD_TIME = new Date().toISOString();

export default defineConfig({
    define: {
        __BUILD_TIME__: JSON.stringify(BUILD_TIME),
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/MaterialDesignForumVuetify4/app.js', 'resources/js/MaterialDesignForumVuetify4/admin.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            // 上传到 public disk 的图片（storage/app/public → public/storage）
            '/storage': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
