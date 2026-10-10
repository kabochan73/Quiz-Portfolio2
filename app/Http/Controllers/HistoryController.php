<?php

namespace App\Http\Controllers;

use App\Enums\AttemptStatus;
use App\Jobs\GradeAttempt;
use App\Models\Attempt;
use App\Models\Section;
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
    public function show(Section $section, Attempt $attempt): View
    {
        Gate::authorize('view', $attempt);

        $section->load('category');
        // 問題ごとのカードで、問題文・回答・採点結果を使う(1件ずつ問い合わせないよう、まとめて読み込む)
        $attempt->load(['answers.question', 'answers.score']);

        // 画面の一番下に出す推定コスト(単価が分からなければ null で、トークン数だけを出す)
        $cost = CostCalculator::fromConfig()->estimate(
            $attempt->model,
            $attempt->input_tokens,
            $attempt->output_tokens,
            $attempt->web_search_requests,
        );

        return view('history.show', compact('section', 'attempt', 'cost'));
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
