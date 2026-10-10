{{--
    問題の作成・編集で共通のフォームの中身(screens.md 2.7)。
    受け取る値: $question(作成時は null)、$submitLabel(「追加する」/「保存する」)、$cancelHref、
              $sectionOptions / $fullSectionIds(編集時のみ。渡したときだけ所属セクションの選択欄を出す)
--}}
<x-ui.textarea name="body" label="問題文" :value="$question?->body" :maxlength="2000" rows="8"
    placeholder="例: TCPとUDPの違いを説明してください。" required autofocus />

@isset($sectionOptions)
    <x-ui.select name="section_id" label="セクション" :options="$sectionOptions" :value="$question->section_id"
        :disabled="$fullSectionIds"
        hint="上限の{{ config('quiz.max_questions_per_section') }}問に達しているセクションには移せません。" required />
@endisset

<div class="flex justify-end gap-2">
    <x-ui.button variant="secondary" href="{{ $cancelHref }}">キャンセル</x-ui.button>
    <x-ui.button type="submit" ::disabled="submitting">{{ $submitLabel }}</x-ui.button>
</div>
