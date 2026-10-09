<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * サイドバーのカテゴリ・セクション一覧を、アプリのレイアウト(layouts/app)に渡す(screens.md 1.1)。
 *
 * View Composer にしているのは、どの画面のコントローラでもサイドバー用のデータを用意しなくて済むようにするため。
 * DB への問い合わせは、カテゴリとセクションの2回だけ(カテゴリが増えても回数は変わらない)。
 *
 * 現在地は、開いているページの URL に含まれるカテゴリ・セクション・問題から決める。
 * - カテゴリ詳細: そのカテゴリを強調して開く
 * - セクション・問題のページ: そのセクションを強調し、属するカテゴリを開く
 */
class SidebarComposer
{
    public function compose(View $view): void
    {
        $view->with('sidebarCategories', $this->categories());
    }

    /**
     * sidebar.blade.php が受け取る形の配列にする。
     *
     * @return array<int, array<string, mixed>>
     */
    private function categories(): array
    {
        [$currentCategoryId, $currentSectionId] = $this->currentLocation();

        // セクション詳細は implementation-plan.md 4-2 で作る。それまではセクションをリンクにしない
        $hasSectionPage = Route::has('sections.show');

        return Category::query()
            ->with(['sections' => fn ($query) => $query->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'href' => route('categories.show', $category),
                // カテゴリ自体を強調するのは、カテゴリ詳細を開いているときだけ
                'current' => $category->id === $currentCategoryId && $currentSectionId === null,
                'open' => $category->id === $currentCategoryId,
                'sections' => $category->sections->map(fn (Section $section) => [
                    'name' => $section->name,
                    'href' => $hasSectionPage ? route('sections.show', $section) : null,
                    'current' => $section->id === $currentSectionId,
                ])->all(),
            ])
            ->all();
    }

    /**
     * 開いているページの URL から、現在地のカテゴリ ID とセクション ID を求める。
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function currentLocation(): array
    {
        $route = request()->route();

        // ルートモデルバインディングで、URL の ID はモデルに置き換わっている
        $section = $route?->parameter('section');
        $question = $route?->parameter('question');
        $category = $route?->parameter('category');

        if ($question instanceof Question) {
            $section = $question->section;
        }

        if ($section instanceof Section) {
            return [$section->category_id, $section->id];
        }

        if ($category instanceof Category) {
            return [$category->id, null];
        }

        return [null, null];
    }
}
