{{--
    スケルトン(design-guide.md 7.9)。読み込み中に、これから出る内容の形をあらかじめ見せる。
    - 大きさは呼び出し側の class で決める(例: class="h-4 w-2/3")
    - 見た目だけの部品なので読み上げでは飛ばす。「採点しています…」などの状態は、周りの文章で伝える
    - 動きを減らす設定の利用者には、app.css の指定で点滅が止まる
--}}
<div aria-hidden="true" {{ $attributes->class('animate-pulse rounded-md bg-zinc-100') }}></div>
