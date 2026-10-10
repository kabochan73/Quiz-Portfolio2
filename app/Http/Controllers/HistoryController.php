<?php

namespace App\Http\Controllers;

use App\Enums\AttemptStatus;
use App\Jobs\GradeAttempt;
use App\Models\Attempt;
use App\Models\Section;
use App\Queries\LatestScores;
use App\Queries\WeakQuestions;
use App\Support\CostCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * セクションの挑戦の履歴(requirements.md 3.4)。履歴は問題単位ではなく、挑戦単位で一覧・詳細を表示する。
 */
class HistoryController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * 履歴一覧(screens.md 2.11)。新しい順に20件ずつ表示する。
     * 問題数と平均点は、一覧を取り出す問い合わせの中でまとめて求める(挑戦ごとに問い合わせない)。
     */
    public function index(Request $request, Section $section): View
    {
        $section->load('category');

        $attempts = $section->attempts()
            ->where('user_id', $request->user()->id)
            ->withCount('answers')
            ->withAvg('scores', 'score')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE);

        $total = $section->attempts()->where('user_id', $request->user()->id)->count();

        return view('history.index', compact('section', 'attempts', 'total'));
    }

    /**
     * 結果画面 = 履歴詳細(screens.md 2.10)。採点直後も、あとから履歴で開いたときも同じ画面。
     * 状態(採点中 / 完了 / 失敗)によって表示を切り替える。
     *
     * URL のセクションと挑戦の組み合わせは、ルートの scopeBindings で確かめている(食い違えば 404)。
     */
    public function show(Section $section, Attempt $attempt, LatestScores $latestScores, WeakQuestions $weakQuestions): View
    {
        Gate::authorize('view', $attempt);

        $section->load('category');
        // 問題ごとのカードで、問題文・回答・採点結果を使う(1件ずつ問い合わせないよう、まとめて読み込む)
        $attempt->load(['answers.question', 'answers.score']);

        // 問題ごとの前回比(screens.md 2.10 (b))。前回 = その問題の、この挑戦より前の採点済みの一番新しい回答。
        // 全問・苦手の種別をまたいでも、同じ問題どうしで比べる(問題そのものの伸びを見るため)
        $previousScores = $latestScores->forQuestions($attempt->answers->pluck('question_id'), $attempt->created_at);

        // 平均点の前回比。前回 = 同じセクション・同じ種別の、ひとつ前の採点済みの挑戦(architecture.md 3.2)。
        // 全問と苦手では問題の組み合わせが違うので、種別をまたいでは比べない
        $previousAverage = $this->previousAverage($attempt);

        // 「苦手だけ再挑戦(N問)」用。この採点の結果も反映した、今のセクションの苦手問題の数
        $weakCount = count($weakQuestions->ids($section));

        // 画面の一番下に出す推定コスト(単価が分からなければ null で、トークン数だけを出す)
        $cost = CostCalculator::fromConfig()->estimate(
            $attempt->model,
            $attempt->input_tokens,
            $attempt->output_tokens,
            $attempt->web_search_requests,
        );

        return view('history.show', compact('section', 'attempt', 'cost', 'previousScores', 'previousAverage', 'weakCount'));
    }

    private function previousAverage(Attempt $attempt): ?float
    {
        $previous = Attempt::query()
            ->where('section_id', $attempt->section_id)
            ->where('user_id', $attempt->user_id)
            ->where('mode', $attempt->mode)
            ->where('status', AttemptStatus::Completed)
            // 同じ時刻の挑戦があっても前後が決まるよう、ID でも比べる
            ->where(fn ($query) => $query
                ->where('created_at', '<', $attempt->created_at)
                ->orWhere(fn ($query) => $query->where('created_at', $attempt->created_at)->where('id', '<', $attempt->id)))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->withAvg('scores', 'score')
            ->first();

        return $previous?->scores_avg_score === null ? null : round($previous->scores_avg_score, 1);
    }

    /**
     * 失敗した挑戦を、回答はそのままで採点し直す(architecture.md 4.6)。
     * 失敗のときだけ受け付け、それ以外(完了・採点中など)は何もせず結果画面へ戻す(二重に採点しないため)。
     */
    public function regrade(Attempt $attempt): RedirectResponse
    {
        Gate::authorize('regrade', $attempt);

        if ($attempt->status === AttemptStatus::Failed) {
            DB::transaction(function () use ($attempt) {
                $attempt->update(['status' => AttemptStatus::Pending, 'error_message' => null]);

                GradeAttempt::dispatch($attempt)->afterCommit();
            });
        }

        // 結果画面へ戻ると、採点中の表示とポーリングが始まり、終われば自動で結果に切り替わる
        return redirect()->route('history.show', [$attempt->section_id, $attempt]);
    }

    /**
     * 採点の状態だけを返す(architecture.md 4.7)。採点中の結果画面が3秒ごとに呼び、
     * 完了・失敗になったらページを再読み込みする。
     */
    public function status(Attempt $attempt): JsonResponse
    {
        Gate::authorize('view', $attempt);

        return response()->json(['status' => $attempt->status->value]);
    }
}
