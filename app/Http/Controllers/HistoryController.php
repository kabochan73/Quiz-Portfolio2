<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * セクションの挑戦の履歴(requirements.md 3.4)。履歴一覧は implementation-plan.md 6-2 で追加する。
 */
class HistoryController extends Controller
{
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

        return view('history.show', compact('section', 'attempt'));
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
