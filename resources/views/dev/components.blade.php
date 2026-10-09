{{--
    共通コンポーネントの見本ページ(implementation-plan.md 2-6、ローカル環境のみ)。
    デザイントークンや部品の見た目を1画面でまとめて確認するためのもの。部品を作るたびにここへ追加する。
    共通レイアウト(2-3)ができるまでは、単独の HTML として書いておく。
--}}
@php
    $brand = ['brand-50', 'brand-100', 'brand-200', 'brand-500', 'brand-600', 'brand-700'];
    $neutral = ['zinc-50', 'zinc-100', 'zinc-200', 'zinc-300', 'zinc-500', 'zinc-700', 'zinc-900'];

    // Tailwind はソース中の文字列からクラスを拾うため、クラス名は組み立てずにそのまま書く
    $swatchClasses = [
        'brand-50' => 'bg-brand-50', 'brand-100' => 'bg-brand-100', 'brand-200' => 'bg-brand-200',
        'brand-500' => 'bg-brand-500', 'brand-600' => 'bg-brand-600', 'brand-700' => 'bg-brand-700',
        'zinc-50' => 'bg-zinc-50', 'zinc-100' => 'bg-zinc-100', 'zinc-200' => 'bg-zinc-200',
        'zinc-300' => 'bg-zinc-300', 'zinc-500' => 'bg-zinc-500', 'zinc-700' => 'bg-zinc-700',
        'zinc-900' => 'bg-zinc-900',
    ];

    $semantic = [
        ['label' => '高得点(80〜100)', 'class' => 'bg-emerald-50 text-emerald-700', 'sample' => '86'],
        ['label' => '中得点(60〜79)', 'class' => 'bg-amber-50 text-amber-700', 'sample' => '72'],
        ['label' => '低得点(0〜59)', 'class' => 'bg-rose-50 text-rose-700', 'sample' => '45'],
        ['label' => '処理中', 'class' => 'bg-brand-50 text-brand-700', 'sample' => '採点中'],
    ];

    $typography = [
        ['role' => '大きな数字', 'class' => 'text-4xl font-semibold tabular-nums text-zinc-900', 'sample' => '72'],
        ['role' => 'ページ見出し', 'class' => 'text-2xl font-semibold tracking-tight text-zinc-900', 'sample' => 'ネットワーク基礎'],
        ['role' => 'セクション見出し', 'class' => 'text-lg font-semibold text-zinc-900', 'sample' => '問題一覧'],
        ['role' => 'カード見出し', 'class' => 'text-base font-medium text-zinc-900', 'sample' => '基本情報技術者試験'],
        ['role' => '本文', 'class' => 'text-body text-zinc-700', 'sample' => 'TCP はコネクション型のプロトコルで、通信の前に 3-way handshake を行い、データの到達順序と信頼性を保証します。'],
        ['role' => 'UIテキスト', 'class' => 'text-sm text-zinc-700', 'sample' => '全問に回答する(8問)'],
        ['role' => '補足', 'class' => 'text-xs text-zinc-500', 'sample' => '10月8日 14:32・8問'],
    ];
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>コンポーネント見本 | Quiz</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="mx-auto max-w-3xl space-y-12 px-4 py-10 lg:px-8">
        <header>
            <p class="text-xs text-zinc-500">開発用(ローカル環境のみ)</p>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">コンポーネント見本</h1>
        </header>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">カラー</h2>

            @foreach (['アクセント(brand)' => $brand, 'ニュートラル(zinc)' => $neutral] as $title => $tokens)
                <div>
                    <p class="mb-2 text-sm text-zinc-500">{{ $title }}</p>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-7">
                        @foreach ($tokens as $token)
                            <div>
                                <div class="h-12 rounded-lg border border-zinc-200 {{ $swatchClasses[$token] }}"></div>
                                <p class="mt-1 text-xs text-zinc-500">{{ $token }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div>
                <p class="mb-2 text-sm text-zinc-500">意味を持つ色</p>
                <div class="flex flex-wrap gap-3">
                    @foreach ($semantic as $item)
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-0.5 text-sm font-medium tabular-nums {{ $item['class'] }}">{{ $item['sample'] }}</span>
                            <span class="text-xs text-zinc-500">{{ $item['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">タイポグラフィ</h2>
            <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white shadow-xs">
                @foreach ($typography as $item)
                    <div class="grid gap-1 p-4 sm:grid-cols-4 sm:gap-4 lg:p-5">
                        <p class="text-xs text-zinc-500">{{ $item['role'] }}</p>
                        <p class="sm:col-span-3 {{ $item['class'] }}">{{ $item['sample'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">角丸と影</h2>
            <div class="flex flex-wrap items-start gap-4">
                <div class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm">ボタン・入力欄(8px)</div>
                <div class="rounded-xl border border-zinc-200 bg-white p-5 text-sm shadow-xs">カード(12px・ごく薄い影)</div>
                <div class="rounded-2xl bg-white p-5 text-sm shadow-xl">モーダル(16px)</div>
            </div>
        </section>
    </main>
</body>
</html>
