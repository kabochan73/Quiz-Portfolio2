<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use App\Models\Section;
use App\Services\AnswerRetentionService;
use App\Services\QuestionPlacement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
        $number = $this->numberInSection($question);

        // 削除の確認モーダルで「一緒に消える回答の履歴の件数」を出すため
        $answerCount = $question->answers()->count();

        return view('questions.show', compact('question', 'number', 'answerCount'));
    }

    /**
     * 問題の編集(screens.md 2.7)。変更できるのは問題文だけ(所属セクションは変更できない)。
     */
    public function edit(Question $question): View
    {
        Gate::authorize('update', $question);

        $question->load('section.category');
        $number = $this->numberInSection($question);

        return view('questions.edit', compact('question', 'number'));
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        Gate::authorize('update', $question);

        $question->update(['body' => $request->validated('body')]);

        return redirect()
            ->route('questions.show', $question)
            ->with('toast', ['type' => 'success', 'message' => '問題を保存しました']);
    }

    /**
     * 問題を削除すると、その問題への回答と採点結果も cascade で消える。
     * その結果、回答が1つもなくなった挑戦(例: この問題だけに苦手モードで回答した挑戦)が履歴に残らないよう、
     * 同じトランザクションで削除する(requirements.md 3.4 の「回答が0件になった挑戦は削除」に合わせる)。
     */
    public function destroy(Question $question, AnswerRetentionService $retention): RedirectResponse
    {
        Gate::authorize('delete', $question);

        $section = $question->section;

        DB::transaction(function () use ($question, $section, $retention) {
            $question->delete();
            $retention->deleteEmptyAttempts($section);
        });

        // screens.md 3章: 削除したら1つ上の階層(セクション詳細)へ戻る
        return redirect()
            ->route('sections.show', $section)
            ->with('toast', ['type' => 'success', 'message' => '問題を削除しました']);
    }

    /**
     * パンくずの「問題 N」用に、セクションの中で何問目か(作成順)を求める。
     */
    private function numberInSection(Question $question): int
    {
        return $question->section->questions()->where('id', '<=', $question->id)->count();
    }
}
