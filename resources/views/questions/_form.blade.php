{{--
    問題の作成・編集で共通のフォームの中身(screens.md 2.7)。
    受け取る値: $question(作成時は null)、$submitLabel(「追加する」/「保存する」)、$cancelHref
--}}
<x-ui.textarea name="body" label="問題文" :value="$question?->body" :maxlength="2000" rows="8"
    placeholder="例: TCPとUDPの違いを説明してください。" required autofocus />

<div class="flex justify-end gap-2">
    <x-ui.button variant="secondary" href="{{ $cancelHref }}">キャンセル</x-ui.button>
    <x-ui.button type="submit" ::disabled="submitting">{{ $submitLabel }}</x-ui.button>
</div>
