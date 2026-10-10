<?php

namespace App\Http\Controllers;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Http\Requests\StoreAnswersRequest;
use App\Jobs\GradeAttempt;
use App\Models\Attempt;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * セクションの全問にまとめて回答する(requirements.md 3.3)。
 * 苦手問題だけに回答するモードは implementation-plan.md 6-5 で追加する。
 */
class AnswerController extends Controller
{
    /**
     * 回答フォーム(screens.md 2.9)。問題が0問のセクションでは開かせない。
     */
    public function create(Section $section): View|RedirectResponse
    {
        $questions = $section->questions()->orderBy('id')->get();

        if ($questions->isEmpty()) {
            return redirect()
                ->route('sections.show', $section)
                ->with('toast', ['type' => 'error', 'message' => 'このセクションにはまだ問題がありません']);
        }

        $section->load('category');

        // radio-group に渡す [値 => 表示名]
        $gradingLevels = collect(GradingLevel::cases())
            ->mapWithKeys(fn (GradingLevel $level) => [$level->value => $level->label()])
            ->all();

        return view('answers.create', compact('section', 'questions', 'gradingLevels'));
    }

    /**
     * 挑戦と回答をまとめて保存する(architecture.md 4.2)。
     * 途中で失敗したときに「回答のない挑戦」などが残らないよう、1つのトランザクションで保存する(v1 の不具合の修正)。
     */
    public function store(StoreAnswersRequest $request, Section $section): RedirectResponse
    {
        $attempt = DB::transaction(function () use ($request, $section) {
            /** @var Attempt $attempt */
            $attempt = $section->attempts()->create([
                'user_id' => $request->user()->id,
                'grading_level' => GradingLevel::from($request->validated('grading_level')),
                'mode' => AttemptMode::All,
                'status' => AttemptStatus::Pending,
            ]);

            // フォームに並んでいた順番を position として保存する(結果画面と AI の採点結果の対応付けに使う)
            foreach (array_values($request->validated('answers')) as $position => $answer) {
                $attempt->answers()->create([
                    'question_id' => $answer['question_id'],
                    'user_id' => $request->user()->id,
                    'position' => $position,
                    'body' => $answer['body'],
                ]);
            }

            // 採点は worker が裏で行う。トランザクションが確定してから投入し(afterCommit)、
            // worker がまだ存在しない挑戦を読もうとする事故を防ぐ(architecture.md 4.2)
            GradeAttempt::dispatch($attempt)->afterCommit();

            return $attempt;
        });

        // 結果画面へリダイレクトする(PRG)。結果画面は GET なので、再読み込みしても回答が再送信されず、
        // 二重に採点されることもない(v1 の不具合の修正)
        return redirect()->route('history.show', [$section, $attempt]);
    }
}
