<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Queries\LatestAttemptAverages;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * カテゴリの管理(requirements.md 3.2)。カテゴリ一覧はログイン後の着地点。
 */
class CategoryController extends Controller
{
    /**
     * カテゴリ一覧(screens.md 2.2)。カードにセクション数・問題数・最終挑戦日を出す。
     * どれも一覧を出す1回の問い合わせの中でまとめて求める(カテゴリごとに問い合わせない)。
     * 最終挑戦日は採点中・失敗の挑戦も含める(挑戦した日として扱うため)。
     */
    public function index(): View
    {
        $categories = Category::query()
            ->withCount(['sections', 'questions'])
            ->withMax('attempts', 'created_at')
            ->orderBy('id')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        // screens.md 3章: 作成後は、そのままセクションを追加できるようカテゴリ詳細へ移る
        return redirect()
            ->route('categories.show', $category)
            ->with('toast', ['type' => 'success', 'message' => 'カテゴリを作成しました']);
    }

    /**
     * カテゴリ詳細(screens.md 2.4)。中のセクションを作成順に、問題数・平均点・最終挑戦日と一緒に並べる。
     * 平均点は直近の「全問」の採点済みの挑戦のもの(LatestAttemptAverages)。
     *
     * 削除の確認モーダルで「一緒に消えるデータの件数」を出すため、問題数と挑戦(履歴)の数も数えておく。
     */
    public function show(Category $category, LatestAttemptAverages $latestAttemptAverages): View
    {
        $sections = $category->sections()
            ->withCount('questions')
            ->withMax('attempts', 'created_at')
            ->orderBy('id')
            ->get();

        $averages = $latestAttemptAverages->forSections($sections->pluck('id'));

        $questionCount = $sections->sum('questions_count');
        $attemptCount = $category->attempts()->count();

        return view('categories.show', compact('category', 'sections', 'averages', 'questionCount', 'attemptCount'));
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('categories.show', $category)
            ->with('toast', ['type' => 'success', 'message' => 'カテゴリを保存しました']);
    }

    /**
     * requirements.md 3.2: 配下のセクション・問題・履歴もまとめて削除する。
     * 削除は DB の外部キーの cascade に任せるので、カテゴリの1行を消すだけでよい。
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        // screens.md 3章: 削除したら1つ上の階層(カテゴリ一覧)へ戻る
        return redirect()
            ->route('categories.index')
            ->with('toast', ['type' => 'success', 'message' => 'カテゴリを削除しました']);
    }
}
