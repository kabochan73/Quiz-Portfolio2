{{--
    セクションの作成・編集で共通のフォームの中身(screens.md 2.5)。
    受け取る値: $section(作成時は null)、$submitLabel(「作成する」/「保存する」)、$cancelHref
--}}
<x-ui.input name="name" label="セクション名" :value="$section?->name" placeholder="例: ネットワーク" required autofocus />

<div class="flex justify-end gap-2">
    <x-ui.button variant="secondary" href="{{ $cancelHref }}">キャンセル</x-ui.button>
    <x-ui.button type="submit" ::disabled="submitting">{{ $submitLabel }}</x-ui.button>
</div>
