<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use App\Models\Section;
use App\Services\QuestionPlacement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * 問題の管理(requirements.md 3.2)。問題は必ずセクションに属するので、
 * 作成はセクション配下の URL、詳細などは問題の ID だけで決まる URL にしている(shallow ルート)。
 */
class QuestionController extends Controller
{
    /**
     * 上限に達しているセクションでは、作成画面を開かせずにセクション詳細へ戻す
     * (ボタンは無効にしているが、URL を直接開かれた場合のため)。
     */
    public function create(Section $section): View|RedirectResponse
    {
        if ($section->isFull()) {
            return redirect()
                ->route('sections.show', $section)
                ->with('toast', [
                    'type' => 'error',
                    'message' => '1セクションに登録できる問題は'.config('quiz.max_questions_per_section').'問までです',
                ]);
        }

        $section->load('category');

        return view('questions.create', compact('section'));
    }

    public function store(QuestionRequest $request, Section $section, QuestionPlacement $placement): RedirectResponse
    {
        $placement->create($section, $request->user(), $request->validated('body'));

        // screens.md 2.7: 続けて次の問題を追加しやすいよう、セクション詳細へ戻る
        return redirect()
            ->route('sections.show', $section)
            ->with('toast', ['type' => 'success', 'message' => '問題を作成しました']);
    }

    /**
     * 問題詳細(screens.md 2.8)。全文を表示する。「最近の点数」は implementation-plan.md 6-6 で追加する。
     */
    public function show(Question $question): View
    {
        Gate::authorize('view', $question);

        $question->load('section.category');

        // パンくずの「問題 N」用に、セクションの中で何問目か(作成順)を求める
        $number = $question->section->questions()->where('id', '<=', $question->id)->count();

        return view('questions.show', compact('question', 'number'));
    }
}
