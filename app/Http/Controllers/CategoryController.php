<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * カテゴリの管理(requirements.md 3.2)。カテゴリ一覧はログイン後の着地点。
 */
class CategoryController extends Controller
{
    /**
     * カテゴリ一覧(screens.md 2.2)。カードにセクション数と問題数を出す。
     * 件数は withCount で、一覧を出す1回の問い合わせの中でまとめて数える(カテゴリごとに問い合わせない)。
     */
    public function index(): View
    {
        $categories = Category::query()
            ->withCount(['sections', 'questions'])
            ->orderBy('id')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        // screens.md 3章: 作成後は、そのままセクションを追加できるようカテゴリ詳細へ移る
        return redirect()
            ->route('categories.show', $category)
            ->with('toast', ['type' => 'success', 'message' => 'カテゴリを作成しました']);
    }

    /**
     * カテゴリ詳細(screens.md 2.4)。中のセクションを作成順に、問題数と一緒に並べる。
     * 平均点・最終挑戦日は implementation-plan.md 6-4 で追加する。
     */
    public function show(Category $category): View
    {
        $sections = $category->sections()
            ->withCount('questions')
            ->orderBy('id')
            ->get();

        return view('categories.show', compact('category', 'sections'));
    }
}
