{{--
    カテゴリ一覧(仮)。ログイン後の着地点として置いている。
    本物の一覧は implementation-plan.md 4-1 で作り、このファイルを置き換える。
--}}
<x-layouts.app title="カテゴリ" :breadcrumbs="[['label' => 'カテゴリ']]">
    <x-ui.page-header title="カテゴリ" />
    <x-ui.empty-state icon="folder" title="カテゴリ一覧は準備中です" description="この画面は現在作成中です。" />
</x-layouts.app>
