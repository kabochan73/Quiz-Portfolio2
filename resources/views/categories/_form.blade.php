{{--
    カテゴリの作成・編集で共通のフォームの中身(screens.md 2.3)。
    作成と編集で違うのは、送信先・メソッド・ボタンの文言だけなので、それ以外をここにまとめる。

    受け取る値: $category(作成時は null)、$submitLabel(「作成する」/「保存する」)、$cancelHref
--}}
<x-ui.input name="name" label="カテゴリ名" :value="$category?->name" placeholder="例: 基本情報" required autofocus />

<div class="flex justify-end gap-2">
    <x-ui.button variant="secondary" href="{{ $cancelHref }}">キャンセル</x-ui.button>
    <x-ui.button type="submit" ::disabled="submitting">{{ $submitLabel }}</x-ui.button>
</div>
