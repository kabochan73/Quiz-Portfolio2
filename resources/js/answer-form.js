// 回答フォームの進捗表示・下書きの自動保存・離脱の確認(screens.md 2.9、architecture.md 7章)。
// answers/create.blade.php から Alpine コンポーネントとして使う。
// - 入力が止まって1秒後に、回答と採点レベルを localStorage に保存する
// - 開いたときに下書きがあれば復元する(ただし、送信エラーで戻ってきたときはサーバーの入力を優先して復元しない)
// - 下書きは問題 ID ごとに持つので、問題が増減しても残っている問題の分は復元できる
// - localStorage が使えない環境(プライベートブラウズなど)では、保存せずにそのまま動く
// - 送信が成功したら、結果画面で下書きを消す(history/show.blade.php)

const SAVE_DELAY_MS = 1000;
const SAVED_MESSAGE_MS = 2500;

export default ({ storageKey, questionIds, hasOldInput }) => ({
    answers: {},
    savedVisible: false,
    submitting: false,
    dirty: false,
    saveTimer: null,
    savedTimer: null,

    init() {
        // サーバーが描いた入力欄の値(old() を含む)から始める
        this.textareas().forEach((textarea) => {
            this.answers[textarea.dataset.questionId] = textarea.value;
        });

        if (!hasOldInput) {
            this.restore();
        }

        window.addEventListener('beforeunload', (event) => {
            if (this.dirty && !this.submitting) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    },

    get answeredCount() {
        return questionIds.filter((id) => this.isAnswered(id)).length;
    },

    isAnswered(questionId) {
        return (this.answers[questionId] ?? '').trim() !== '';
    },

    // フォーム内の入力(回答欄・採点レベル)をまとめて受け取る
    onInput(event) {
        const questionId = event.target.dataset.questionId;
        if (questionId) {
            this.answers[questionId] = event.target.value;
        }

        this.dirty = true;
        clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(() => this.save(), SAVE_DELAY_MS);
    },

    onSubmit() {
        this.submitting = true;
        clearTimeout(this.saveTimer);
    },

    save() {
        try {
            localStorage.setItem(storageKey, JSON.stringify({
                grading_level: this.gradingLevel(),
                answers: this.answers,
                saved_at: new Date().toISOString(),
            }));
        } catch {
            return; // 保存できない環境では何もしない
        }

        this.savedVisible = true;
        clearTimeout(this.savedTimer);
        this.savedTimer = setTimeout(() => (this.savedVisible = false), SAVED_MESSAGE_MS);
    },

    restore() {
        let draft;
        try {
            draft = JSON.parse(localStorage.getItem(storageKey) ?? 'null');
        } catch {
            return;
        }
        if (!draft) return;

        let restored = false;
        this.textareas().forEach((textarea) => {
            const value = draft.answers?.[textarea.dataset.questionId];
            if (typeof value === 'string' && value !== '') {
                this.setTextarea(textarea, value);
                restored = true;
            }
        });

        const level = this.$root.querySelector(`input[name="grading_level"][value="${draft.grading_level}"]`);
        if (level) level.checked = true;

        if (restored) {
            this.dirty = true;
            // トーストの表示場所(レイアウトの末尾)の初期化を待ってから知らせる
            setTimeout(() => this.$dispatch('toast', { type: 'success', message: '前回の下書きを復元しました' }));
        }
    },

    discard() {
        try {
            localStorage.removeItem(storageKey);
        } catch {
            // 消せない環境でも、画面の入力は空にする
        }

        this.textareas().forEach((textarea) => this.setTextarea(textarea, ''));
        clearTimeout(this.saveTimer);
        this.dirty = false;
        this.savedVisible = false;
    },

    textareas() {
        return this.$root.querySelectorAll('textarea[data-question-id]');
    },

    gradingLevel() {
        return this.$root.querySelector('input[name="grading_level"]:checked')?.value ?? null;
    },

    // 値を入れたうえで input イベントを起こし、文字数カウンタなども更新させる
    setTextarea(textarea, value) {
        textarea.value = value;
        this.answers[textarea.dataset.questionId] = value;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    },
});
