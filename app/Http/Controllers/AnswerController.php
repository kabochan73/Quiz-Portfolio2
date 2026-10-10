<?php

namespace App\Http\Controllers;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Http\Requests\StoreAnswersRequest;
use App\Jobs\GradeAttempt;
use App\Models\Attempt;
use App\Models\Section;
use App\Queries\WeakQuestions;
use App\Services\AnswerRetentionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * セクションの問題にまとめて回答する(requirements.md 3.3)。
 * 種別は2つ: 全問(mode=all)/ 苦手問題だけ(mode=weak、最新の点数が基準点未満の問題)。
 */
class AnswerController extends Controller
{
    /**
     * 回答フォーム(screens.md 2.9)。?mode=weak なら苦手問題だけを並べる。
     * 対象の問題が0問なら開かせず、セクション詳細へ戻す。
     */
    public function create(Request $request, Section $section, WeakQuestions $weakQuestions): View|RedirectResponse
    {
        $mode = AttemptMode::tryFrom((string) $request->query('mode')) ?? AttemptMode::All;

        $questions = $section->questions()
            ->when($mode === AttemptMode::Weak, fn ($query) => $query->whereIn('id', $weakQuestions->ids($section)))
            ->orderBy('id')
            ->get();

        if ($questions->isEmpty()) {
            return redirect()
                ->route('sections.show', $section)
                ->with('toast', ['type' => 'error', 'message' => $mode === AttemptMode::Weak
                    ? '苦手な問題はありません'
                    : 'このセクションにはまだ問題がありません']);
        }

        $section->load('category');

        // radio-group に渡す [値 => 表示名]
        $gradingLevels = collect(GradingLevel::cases())
            ->mapWithKeys(fn (GradingLevel $level) => [$level->value => $level->label()])
            ->all();

        return view('answers.create', compact('section', 'questions', 'gradingLevels', 'mode'));
    }

    /**
     * 挑戦と回答をまとめて保存する(architecture.md 4.2)。
     * 途中で失敗したときに「回答のない挑戦」などが残らないよう、1つのトランザクションで保存する(v1 の不具合の修正)。
     */
    public function store(StoreAnswersRequest $request, Section $section, AnswerRetentionService $retention): RedirectResponse
    {
        $attempt = DB::transaction(function () use ($request, $section, $retention) {
            /** @var Attempt $attempt */
            $attempt = $section->attempts()->create([
                'user_id' => $request->user()->id,
                'grading_level' => GradingLevel::from($request->validated('grading_level')),
                'mode' => AttemptMode::from($request->validated('mode')),
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

            // 同じ問題の古い回答を整理する(直近10件だけ残す、requirements.md 3.4)。
            // 今回の回答は一番新しいので必ず残る
            $retention->prune($section, collect($request->validated('answers'))->pluck('question_id'));

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
