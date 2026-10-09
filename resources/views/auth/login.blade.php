{{-- ログイン画面(screens.md 2.1)。会員登録の導線は置かない(requirements.md 3.1) --}}
<x-layouts.minimal title="ログイン">
    <div class="mb-8 text-center">
        <p class="text-2xl font-semibold tracking-tight text-zinc-900">{{ config('app.name') }}</p>
        <p class="mt-1 text-sm text-zinc-500">AIが採点する学習ノート</p>
    </div>

    <x-ui.card class="space-y-5">
        {{-- 認証の失敗・試行回数の制限は、どちらの入力欄の問題かを示さず、フォームの上に1つだけ出す --}}
        @error(App\Http\Requests\Auth\LoginRequest::ERROR_KEY)
            <div role="alert" class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('login') }}" class="space-y-5"
            x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <x-ui.input name="email" type="email" label="メールアドレス" autocomplete="username" required autofocus />
            <x-ui.input name="password" type="password" label="パスワード" autocomplete="current-password" required />
            <x-ui.checkbox name="remember" label="ログイン状態を保持する" />
            <x-ui.button type="submit" class="w-full" ::disabled="submitting">ログイン</x-ui.button>
        </form>
    </x-ui.card>
</x-layouts.minimal>
