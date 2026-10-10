import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import answerForm from './answer-form';
import attemptPoller from './attempt-poller';
import toastStack from './toast';

// collapse: 結果画面の問題カードの開閉(x-collapse、design-guide.md 5章)
// focus: 確認モーダル表示中にフォーカスを閉じ込める(x-trap、design-guide.md 8章)
Alpine.plugin(collapse);
Alpine.plugin(focus);

// 複数の画面で使う Alpine コンポーネント
Alpine.data('toastStack', toastStack);
Alpine.data('attemptPoller', attemptPoller);
Alpine.data('answerForm', answerForm);

// Blade 内のインラインスクリプトや開発者ツールから参照できるようにしておく
window.Alpine = Alpine;

Alpine.start();
