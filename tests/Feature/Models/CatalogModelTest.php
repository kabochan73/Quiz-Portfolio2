<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

it('カテゴリ → セクション → 問題の順にたどれる', function () {
    $question = Question::factory()->create();

    $section = $question->section;
    $category = $section->category;

    expect($category->sections->pluck('id')->all())->toBe([$section->id])
        ->and($section->questions->pluck('id')->all())->toBe([$question->id]);
});

it('問題とユーザーを相互にたどれる', function () {
    $user = User::factory()->create();
    $question = Question::factory()->for($user)->create();

    expect($question->user->is($user))->toBeTrue()
        ->and($user->questions->pluck('id')->all())->toBe([$question->id]);
});

it('問題数が上限に達するとセクションは満杯になる', function () {
    $section = Section::factory()->create();

    Question::factory()->count(9)->for($section)->create();
    expect($section->isFull())->toBeFalse();

    Question::factory()->for($section)->create();
    expect($section->isFull())->toBeTrue();
});

it('満杯かどうかの判定は、問題数の上限の設定に従う', function () {
    config(['quiz.max_questions_per_section' => 2]);
    $section = Section::factory()->create();

    Question::factory()->count(2)->for($section)->create();

    expect($section->isFull())->toBeTrue();
});

it('問題の抜粋は、40文字を超えると末尾を「…」にする', function () {
    $question = Question::factory()->make(['body' => str_repeat('あ', 41)]);

    expect($question->excerpt())->toBe(str_repeat('あ', 40).'…');
});

it('問題の抜粋は、40文字以内ならそのまま表示する', function () {
    $question = Question::factory()->make(['body' => str_repeat('あ', 40)]);

    expect($question->excerpt())->toBe(str_repeat('あ', 40));
});

it('問題の抜粋は、改行や連続した空白を1つの空白に詰める', function () {
    $question = Question::factory()->make(['body' => "TCPと\n\nUDPの    違い"]);

    expect($question->excerpt())->toBe('TCPと UDPの 違い');
});

it('カテゴリの Factory は日本語の名前で作る', function () {
    expect(Category::factory()->make()->name)->toMatch('/\p{Han}|\p{Katakana}|[A-Za-z]/u');
});
