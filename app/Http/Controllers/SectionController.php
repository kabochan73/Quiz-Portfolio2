<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionRequest;
use App\Models\Category;
use App\Models\Section;
use App\Queries\LatestScores;
use App\Queries\WeakQuestions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * セクションの管理(requirements.md 3.2)。セクションは必ずカテゴリに属するので、
 * 作成はカテゴリ配下の URL、詳細などはセクションの ID だけで決まる URL にしている(shallow ルート)。
 */
class SectionController extends Controller
{
    public function create(Category $category): View
    {
        return view('sections.create', compact('category'));
    }

    public function store(SectionRequest $request, Category $category): RedirectResponse
    {
        $section = $category->sections()->create($request->validated());

        // 作成後は、そのまま問題を追加できるようセクション詳細へ移る
        return redirect()
            ->route('sections.show', $section)
            ->with('toast', ['type' => 'success', 'message' => 'セクションを作成しました']);
    }

    /**
     * セクション詳細(screens.md 2.6)。問題を作成順に、本文の抜粋で並べる。
     * 問題ごとに最新の点数のバッジを出し、どの問題が苦手かをひと目で分かるようにする。
     */
    public function show(Section $section, LatestScores $latestScores, WeakQuestions $weakQuestions): View
    {
        $section->load('category');
        $questions = $section->questions()->orderBy('id')->get();

        // 問題ごとの最新の点数(点数バッジ用)。一度も採点されていない問題は含まれない
        $scores = $latestScores->forQuestions($questions->pluck('id'));
        // 「苦手だけ再挑戦(N問)」用。求めた最新の点数から数え、同じ問い合わせを繰り返さない
        $weakCount = count($weakQuestions->filter($scores));
        $maxQuestions = config('quiz.max_questions_per_section');

        // 削除の確認モーダルで「一緒に消える履歴の件数」を出すため
        $attemptCount = $section->attempts()->count();

        return view('sections.show', compact('section', 'questions', 'scores', 'weakCount', 'maxQuestions', 'attemptCount'));
    }

    public function edit(Section $section): View
    {
        $section->load('category');

        return view('sections.edit', compact('section'));
    }

    /**
     * 名前だけを更新する。所属カテゴリは変更できない(requirements.md 3.2)。
     */
    public function update(SectionRequest $request, Section $section): RedirectResponse
    {
        $section->update($request->validated());

        return redirect()
            ->route('sections.show', $section)
            ->with('toast', ['type' => 'success', 'message' => 'セクションを保存しました']);
    }

    /**
     * requirements.md 3.2: セクションの問題・履歴もまとめて削除する(DB の cascade に任せる)。
     */
    public function destroy(Section $section): RedirectResponse
    {
        $category = $section->category;
        $section->delete();

        // screens.md 3章: 削除したら1つ上の階層(カテゴリ詳細)へ戻る
        return redirect()
            ->route('categories.show', $category)
            ->with('toast', ['type' => 'success', 'message' => 'セクションを削除しました']);
    }
}
