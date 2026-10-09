{{--
    サイドバーのない、中身を画面中央に置くだけのレイアウト。
    - エラーページ: DB やログイン情報が使えない状況(500 など)でも表示できるよう、サイドバーを持たない
    - ログイン画面(screens.md 2.1): カードを画面中央に置く

    例: <x-layouts.minimal title="ページが見つかりません"> ... </x-layouts.minimal>
--}}
@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "{$title} | " : '' }}{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh">
    <main class="flex min-h-dvh items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
