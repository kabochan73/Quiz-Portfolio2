<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionRequest;
use App\Models\Category;
use App\Models\Section;
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
     * 回答・履歴の操作エリアはフェーズ5・6、問題ごとの最新点数は implementation-plan.md 6-4 で追加する。
     */
    public function show(Section $section): View
    {
        $section->load('category');
        $questions = $section->questions()->orderBy('id')->get();
        $maxQuestions = config('quiz.max_questions_per_section');

        return view('sections.show', compact('section', 'questions', 'maxQuestions'));
    }
}
