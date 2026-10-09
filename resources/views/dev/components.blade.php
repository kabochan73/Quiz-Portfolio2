{{--
    共通コンポーネントの見本ページ(implementation-plan.md 2-6、ローカル環境のみ)。
    デザイントークンや部品の見た目を1画面でまとめて確認するためのもの。部品を作るたびにここへ追加する。
    アプリ全体のレイアウトの上に載せ、PC / スマホでのサイドバー・ドロワーの動きも一緒に確かめる。
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

    // サイドバーの見本用の仮データ。本番では View Composer が実データを共有する(implementation-plan.md 4-4)
    view()->share('sidebarCategories', [
        ['name' => '基本情報', 'href' => '#', 'current' => false, 'open' => true, 'sections' => [
            ['name' => 'ネットワーク', 'href' => '#', 'current' => true],
            ['name' => 'データベース', 'href' => '#', 'current' => false],
            ['name' => 'セキュリティ', 'href' => '#', 'current' => false],
        ]],
        ['name' => '英語', 'href' => '#', 'current' => false, 'sections' => [
            ['name' => '英文法', 'href' => '#', 'current' => false],
        ]],
        ['name' => 'Laravel', 'href' => '#', 'current' => false, 'sections' => [
            ['name' => 'ルーティング', 'href' => '#', 'current' => false],
            ['name' => 'Eloquent', 'href' => '#', 'current' => false],
        ]],
        ['name' => 'セクションのないカテゴリ', 'href' => '#', 'current' => false, 'sections' => []],
    ]);
@endphp
<x-layouts.app title="コンポーネント見本">
    <div class="space-y-12">
        <header>
            <p class="text-xs text-zinc-500">開発用(ローカル環境のみ)</p>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">コンポーネント見本</h1>
        </header>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">カラー</h2>

            @foreach (['アクセント(brand)' => $brand, 'ニュートラル(zinc)' => $neutral] as $groupTitle => $tokens)
                <div>
                    <p class="mb-2 text-sm text-zinc-500">{{ $groupTitle }}</p>
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
            <h2 class="text-lg font-semibold text-zinc-900">ボタン <code class="text-sm font-normal text-zinc-500">&lt;x-ui.button&gt;</code></h2>

            @foreach (['md' => '標準(md)', 'sm' => '小(sm)'] as $size => $label)
                <div>
                    <p class="mb-2 text-sm text-zinc-500">{{ $label }}</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button :size="$size">全問に回答する</x-ui.button>
                        <x-ui.button variant="secondary" :size="$size">苦手だけ再挑戦</x-ui.button>
                        <x-ui.button variant="ghost" :size="$size">履歴を見る</x-ui.button>
                        <x-ui.button variant="danger" :size="$size">削除する</x-ui.button>
                    </div>
                </div>
            @endforeach

            <div>
                <p class="mb-2 text-sm text-zinc-500">無効・リンク・幅いっぱい</p>
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button disabled>送信中…</x-ui.button>
                        <x-ui.button variant="secondary" disabled>苦手だけ再挑戦(0問)</x-ui.button>
                        <x-ui.button variant="secondary" href="#">リンクのボタン</x-ui.button>
                    </div>
                    <x-ui.button type="submit" class="w-full">採点する(8問)</x-ui.button>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">カード <code class="text-sm font-normal text-zinc-500">&lt;x-ui.card&gt;</code></h2>

            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.card>
                    <p class="text-base font-medium text-zinc-900">通常のカード</p>
                    <p class="mt-1 text-xs text-zinc-500">リンクではない、情報をまとめる入れ物</p>
                </x-ui.card>
                <x-ui.card href="#">
                    <p class="text-base font-medium text-zinc-900">基本情報</p>
                    <p class="mt-1 text-xs text-zinc-500">3セクション・24問・最終挑戦 10/7</p>
                </x-ui.card>
            </div>

            <x-ui.card :padding="false" class="divide-y divide-zinc-200">
                @foreach (['ネットワーク' => '8問', 'データベース' => '10問', 'セキュリティ' => '6問'] as $name => $count)
                    <a href="#" class="flex min-h-11 items-center justify-between px-4 py-3 text-sm transition-colors first:rounded-t-xl last:rounded-b-xl hover:bg-zinc-50 lg:px-5">
                        <span class="font-medium text-zinc-900">{{ $name }}</span>
                        <span class="text-zinc-500">{{ $count }}</span>
                    </a>
                @endforeach
            </x-ui.card>
            <p class="text-xs text-zinc-500">↑ :padding="false" にして、中に行を並べる使い方(セクション一覧など)</p>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">点数バッジ・前回比 <code class="text-sm font-normal text-zinc-500">&lt;x-ui.score-badge / score-delta&gt;</code></h2>

            <div>
                <p class="mb-2 text-sm text-zinc-500">点数の境目(苦手の基準点: {{ config('quiz.weak_threshold') }}点)</p>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ([100, 80, 79, 60, 59, 0, null] as $score)
                        <x-ui.score-badge :score="$score" />
                    @endforeach
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm text-zinc-500">大きいサイズ(lg)と前回比</p>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                    @foreach ([[82, 70], [45, 52], [72, 72], [64, null]] as [$current, $previous])
                        <div class="flex items-center gap-2">
                            <x-ui.score-badge :score="$current" size="lg" />
                            <x-ui.score-delta :current="$current" :previous="$previous" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">バッジ <code class="text-sm font-normal text-zinc-500">&lt;x-ui.badge / status-badge&gt;</code></h2>

            <div>
                <p class="mb-2 text-sm text-zinc-500">採点状態</p>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (\App\Enums\AttemptStatus::cases() as $status)
                        <x-ui.status-badge :status="$status" />
                    @endforeach
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm text-zinc-500">種別</p>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (\App\Enums\AttemptMode::cases() as $mode)
                        <x-ui.badge>{{ $mode->label() }}</x-ui.badge>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm text-zinc-500">組み合わせの例(履歴一覧の1行)</p>
                <x-ui.card :padding="false" class="divide-y divide-zinc-200">
                    @foreach ([
                        ['10/8 14:32', \App\Enums\AttemptMode::All, '普通', 8, 72, \App\Enums\AttemptStatus::Completed],
                        ['10/7 21:05', \App\Enums\AttemptMode::Weak, '厳しい', 3, 54, \App\Enums\AttemptStatus::Completed],
                        ['10/5 09:12', \App\Enums\AttemptMode::All, '普通', 8, null, \App\Enums\AttemptStatus::Grading],
                        ['10/3 22:40', \App\Enums\AttemptMode::All, '優しい', 8, null, \App\Enums\AttemptStatus::Failed],
                    ] as [$date, $mode, $level, $count, $score, $status])
                        <div class="flex min-h-11 items-center gap-3 px-4 py-3 text-sm lg:px-5">
                            <span class="tabular-nums text-zinc-500">{{ $date }}</span>
                            <x-ui.badge>{{ $mode->label() }}</x-ui.badge>
                            <span class="text-zinc-700">{{ $level }}・{{ $count }}問</span>
                            <span class="ml-auto">
                                @if ($status === \App\Enums\AttemptStatus::Completed)
                                    <x-ui.score-badge :score="$score" />
                                @else
                                    <x-ui.status-badge :status="$status" />
                                @endif
                            </span>
                        </div>
                    @endforeach
                </x-ui.card>
            </div>
        </section>

        @php
            // エラー表示の見本を出すため、この見本ページでだけ、架空のエラーをビューに共有する
            // (本来はバリデーション失敗時に Laravel が自動で共有するもの)。
            view()->share('errors', (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag([
                'sample_error_name' => 'カテゴリ名を入力してください。',
                'sample_error_body' => '問題文は2000文字以内で入力してください。',
                'sample_error_section' => '1セクションに登録できる問題は10問までです。',
            ])));
        @endphp

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">フォーム <code class="text-sm font-normal text-zinc-500">&lt;x-ui.input / textarea / select&gt;</code></h2>

            <div class="grid gap-6 sm:grid-cols-2">
                <x-ui.card class="space-y-5">
                    <p class="text-sm text-zinc-500">通常</p>
                    <x-ui.input name="sample_name" label="カテゴリ名" placeholder="例: 基本情報" hint="あとから変更できます" />
                    <x-ui.textarea name="sample_body" label="問題文" :maxlength="100" rows="4"
                        value="TCPとUDPの違いを説明してください。" />
                    <x-ui.select name="sample_section" label="所属セクション" placeholder="選択してください"
                        :options="[1 => 'ネットワーク(8問)', 2 => 'データベース(10問・上限)', 3 => 'セキュリティ(6問)']"
                        :disabled="[2]" />
                </x-ui.card>

                <x-ui.card class="space-y-5">
                    <p class="text-sm text-zinc-500">エラー</p>
                    <x-ui.input name="sample_error_name" label="カテゴリ名" />
                    <x-ui.textarea name="sample_error_body" label="問題文" :maxlength="20" rows="4"
                        value="この文章は上限の20文字を超えているので、文字数が赤く表示されます。" />
                    <x-ui.select name="sample_error_section" label="所属セクション" :value="2"
                        :options="[1 => 'ネットワーク(8問)', 2 => 'データベース(10問・上限)']" />
                </x-ui.card>
            </div>
            <p class="text-xs text-zinc-500">文字数は入力すると Alpine でその場で数え直されます。</p>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">角丸と影</h2>
            <div class="flex flex-wrap items-start gap-4">
                <div class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm">ボタン・入力欄(8px)</div>
                <div class="rounded-xl border border-zinc-200 bg-white p-5 text-sm shadow-xs">カード(12px・ごく薄い影)</div>
                <div class="rounded-2xl bg-white p-5 text-sm shadow-xl">モーダル(16px)</div>
            </div>
        </section>
    </div>
</x-layouts.app>
