import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // design-guide.md 3.1: 英数字は Inter、日本語は Noto Sans JP。
            // ビルド時に Google Fonts から取り込み、自サイトから配信する(表示のたびに外部へ取りに行かない)。
            fonts: [
                google('Inter', {
                    weights: [400, 500, 600],
                }),
                google('Noto Sans JP', {
                    weights: [400, 500, 700],
                    subsets: ['japanese', 'latin'],
                    // 日本語フォントは文字の範囲ごとに多数のファイルに分かれているため、全部を先読みしない。
                    // ブラウザがページで使われている文字の分だけを取りに行く。
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
