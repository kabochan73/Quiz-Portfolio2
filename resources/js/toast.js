// トースト通知の管理(design-guide.md 7.5)。toast-stack.blade.php から Alpine コンポーネントとして使う。
// - サーバーから渡されたトースト(セッションの toast)は、表示直後に出す
// - 画面の中からは $dispatch('toast', { type: 'success', message: '...' }) で出す
// - 4秒で自動的に消える。× ボタンでも閉じられる

const AUTO_DISMISS_MS = 4000;
// 消えるときのトランジション(200ms)が終わってから配列から取り除く
const LEAVE_MS = 200;

export default (initialToasts = []) => ({
    toasts: [],
    nextId: 1,

    init() {
        initialToasts.forEach((toast) => this.add(toast));
    },

    add({ type = 'success', message }) {
        const id = this.nextId++;
        // 最初は非表示で追加し、次の描画で表示に切り替える(出てくるときのトランジションを効かせるため)
        this.toasts.push({ id, type: type === 'error' ? 'error' : 'success', message, visible: false });
        this.$nextTick(() => {
            const toast = this.toasts.find((t) => t.id === id);
            if (toast) toast.visible = true;
        });

        setTimeout(() => this.remove(id), AUTO_DISMISS_MS);
    },

    remove(id) {
        const toast = this.toasts.find((t) => t.id === id);
        if (!toast) return;

        toast.visible = false;
        setTimeout(() => {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        }, LEAVE_MS);
    },
});
