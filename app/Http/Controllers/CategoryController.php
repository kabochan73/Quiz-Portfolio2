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
        Category::create($request->validated());

        // screens.md 3章では作成後にカテゴリ詳細へ移るが、詳細画面はまだないので一覧へ戻す
        // (implementation-plan.md 4-1 の詳細画面を作るときに変更する)
        return redirect()
            ->route('categories.index')
            ->with('toast', ['type' => 'success', 'message' => 'カテゴリを作成しました']);
    }
}
