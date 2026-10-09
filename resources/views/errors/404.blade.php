{{-- 存在しないページ。権限のないページ(403)もこの画面を使う(errors/403.blade.php) --}}
<x-layouts.minimal title="ページが見つかりません">
    <div class="text-center">
        <p class="text-sm font-medium text-brand-600 tabular-nums">404</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900">ページが見つかりません</h1>
        <p class="mt-2 text-sm leading-relaxed text-zinc-500">URL が間違っているか、ページが削除された可能性があります。</p>
        <x-ui.button href="{{ url('/') }}" class="mt-8">トップへ戻る</x-ui.button>
    </div>
</x-layouts.minimal>
