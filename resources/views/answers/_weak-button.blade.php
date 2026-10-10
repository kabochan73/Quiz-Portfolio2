{{--
    「苦手だけ再挑戦(N問)」のボタン(screens.md 2.6 / 2.10 (b))。セクション詳細と結果画面で使う。
    苦手な問題が0問のときは、ボタンを無効にして理由を添える。

    受け取る値: $section、$weakCount(苦手な問題の数)
--}}
@if ($weakCount > 0)
    <x-ui.button variant="secondary" href="{{ route('answers.create', [$section, 'mode' => 'weak']) }}" class="w-full sm:w-auto">
        苦手だけ再挑戦({{ $weakCount }}問)
    </x-ui.button>
@else
    <span class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
        <x-ui.button variant="secondary" disabled class="w-full sm:w-auto">苦手だけ再挑戦</x-ui.button>
        <span class="text-xs text-zinc-500">苦手な問題はありません</span>
    </span>
@endif
