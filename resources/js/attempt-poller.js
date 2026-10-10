// 採点中の結果画面で、採点が終わったかを定期的に確かめる(screens.md 2.10 (a)、architecture.md 4.7)。
// history/show.blade.php から Alpine コンポーネントとして使う。
// - 3秒ごとに状態を確かめ、完了・失敗になったらページを再読み込みして結果を出す
// - 5分たっても終わらなければ確認をやめ、再読み込みを案内する(API を呼び続けないように)
// - 通信エラーのときは止めずに、次の確認でもう一度試す

const INTERVAL_MS = 3000;
const GIVE_UP_MS = 5 * 60 * 1000;
const FINISHED_STATUSES = ['completed', 'failed'];

export default (statusUrl) => ({
    timedOut: false,
    timer: null,
    startedAt: Date.now(),

    init() {
        this.schedule();
    },

    // ページを離れたら、確認も止める
    destroy() {
        clearTimeout(this.timer);
    },

    // 前の確認が終わってから次を予約する(setInterval だと、通信が遅いときに確認が重なるため)
    schedule() {
        this.timer = setTimeout(() => this.check(), INTERVAL_MS);
    },

    async check() {
        if (Date.now() - this.startedAt >= GIVE_UP_MS) {
            this.timedOut = true;
            return;
        }

        try {
            const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });

            if (response.ok) {
                const { status } = await response.json();

                if (FINISHED_STATUSES.includes(status)) {
                    window.location.reload();
                    return;
                }
            }
        } catch {
            // 通信エラー・ログイン切れなどは、次の確認でもう一度試す
        }

        this.schedule();
    },
});
