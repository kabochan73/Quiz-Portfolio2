{{-- サーバー内部のエラー。詳しい原因は画面に出さず、ログで確認する --}}
<x-layouts.minimal title="エラーが発生しました">
    <div class="text-center">
        <p class="text-sm font-medium text-brand-600 tabular-nums">500</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900">エラーが発生しました</h1>
        <p class="mt-2 text-sm leading-relaxed text-zinc-500">申し訳ありません。しばらくしてから再度お試しください。</p>
        <x-ui.button href="{{ url('/') }}" class="mt-8">トップへ戻る</x-ui.button>
    </div>
</x-layouts.minimal>
