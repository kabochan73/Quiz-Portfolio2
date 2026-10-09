{{--
    CSRF トークンの期限切れ。フォームを長時間開いたまま送信したときに出る。
    Laravel 標準の「Page Expired」画面が英語で出るのを避けるために用意している。
--}}
<x-layouts.minimal title="ページの有効期限が切れました">
    <div class="text-center">
        <p class="text-sm font-medium text-brand-600 tabular-nums">419</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900">ページの有効期限が切れました</h1>
        <p class="mt-2 text-sm leading-relaxed text-zinc-500">画面を長い時間開いたままにしていたため、送信できませんでした。お手数ですが、もう一度操作してください。</p>
        <x-ui.button href="{{ url('/') }}" class="mt-8">トップへ戻る</x-ui.button>
    </div>
</x-layouts.minimal>
