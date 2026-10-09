# Quiz-Portfolio2

自由記述の解答を Claude API が採点する、個人用の学習アプリ(ポートフォリオ改良版 v2)。前作 Quiz-Portfolio(v1)を、不具合の修正・UI/UX の刷新・学習機能の追加を目的にスクラッチで作り直している。

## ドキュメント

作業の前に、関係するドキュメントを読むこと。仕様の判断に迷ったら、ドキュメントを根拠にする。

| ファイル | 内容 |
|---|---|
| [doc/requirements.md](doc/requirements.md) | 要件定義(何を作るか) |
| [doc/design-guide.md](doc/design-guide.md) | UIデザインガイド(色・文字・共通コンポーネント) |
| [doc/screens.md](doc/screens.md) | 画面設計(ワイヤーフレーム・画面遷移・文言) |
| [doc/architecture.md](doc/architecture.md) | DB・採点処理の設計 |
| [doc/implementation-plan.md](doc/implementation-plan.md) | 実装の順番とテストの方針 |

仕様を変えた場合は、コードと一緒にドキュメントも更新する。

## 技術スタック

- PHP 8.5 / Laravel 13(Blade、Livewire は使わない)
- Tailwind CSS v4 + Alpine.js
- PostgreSQL、キューは database ドライバ
- AI採点: Claude API
- テスト: Pest
- デプロイ: Railway(web と worker の2サービス)

## 開発環境

ローカルに PHP / Composer は不要。Docker で完結させる。

```sh
docker compose up -d                       # app / nginx / db / worker を起動
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test   # テスト
docker compose exec app ./vendor/bin/pint  # コード整形
```

- アプリ: http://localhost:8000
- `.env` の `GRADING_DRIVER=fake` にすると、Claude API を呼ばずに固定の採点結果を返す(画面の作り込みはこれで行う)

## 開発ルール

### UI

- 色・サイズは `resources/css/app.css` の `@theme` のトークンを使う。色コードや任意の値(`text-[#xxx]` など)を直接書かない
- ボタン・入力欄・カード・バッジなどは `resources/views/components/ui/` の共通コンポーネントを使う。画面ごとに同じスタイルを書き直さない
- 新しい部品が必要になったら、まず共通コンポーネントとして作り、design-guide.md に追記する
- スマホ(幅 375px)と PC(幅 1280px)の両方で見た目を確認する
- 削除の確認に `confirm()` を使わない。確認モーダルを使う
- 画面の文言は screens.md の「4. 表示する文言」に合わせる(丁寧語、「!」は使わない)

### コード

- コメントは日本語で書き、「なぜそうしたか」を残す。仕様に由来する場合は根拠のドキュメントの章を書く(例: `// requirements.md 3.3: 〜のため`)
- 入力チェックは FormRequest、権限チェックは Policy で行う
- 複数のテーブルを更新する処理はトランザクションで囲む
- 外部 API(Claude)を呼ぶ処理は `GradingService` インターフェースの後ろに置き、テストではフェイクに差し替える
- 状態や種別は文字列で比べず、Enum を使う

### テスト

- 機能を追加・変更したら、同じコミットでテストを追加・更新する
- DB や API に依存しないロジックは Unit テスト、画面や Job の流れは Feature テストで確認する
- テストで実際の Claude API を呼ばない

### Git

- コミットメッセージは日本語で、何をしたかを1行で書く(例: 「カテゴリのCRUD機能を実装」)
- コミット前に Pint とテストを通す
