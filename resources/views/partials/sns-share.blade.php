@php
    $text = $text ?? '';
    $url = $url ?? url()->current();
    $title = $title ?? config('app.name', 'Stampippi');
    $size = $size ?? 'sm';

    $encodedText = rawurlencode($text);
    $encodedUrl = rawurlencode($url);

    $xShareUrl = "https://twitter.com/intent/tweet?text={$encodedText}&url={$encodedUrl}";
    $lineShareUrl = "https://line.me/R/msg/text/?{$encodedText}%20{$encodedUrl}";

    $btnClass = match($size) {
        'xs' => 'text-[11px] px-2 py-1 gap-1',
        'md' => 'text-sm px-3.5 py-2 gap-2',
        'lg' => 'text-base px-4 py-2.5 gap-2',
        default => 'text-xs px-2.5 py-1.5 gap-1.5',
    };
@endphp

<div class="inline-flex items-center flex-wrap gap-1.5">
    @if (!empty($label))
        <span class="text-[11px] font-bold text-slate-500 mr-0.5">{{ $label }}:</span>
    @endif

    <!-- X (Twitter) -->
    <a href="{{ $xShareUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="inline-flex items-center font-bold text-white bg-black hover:bg-neutral-800 rounded-lg transition shadow-xs {{ $btnClass }}"
       title="X (Twitter) でポスト">
        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
        </svg>
        <span>ポスト</span>
    </a>

    <!-- LINE -->
    <a href="{{ $lineShareUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="inline-flex items-center font-bold text-white bg-[#06C755] hover:bg-[#05b34c] rounded-lg transition shadow-xs {{ $btnClass }}"
       title="LINE で送る">
        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M24 10.3c0-4.6-4.9-8.3-11-8.3S2 5.7 2 10.3c0 4.1 3.7 7.6 8.8 8.2.3.1.8.2.9.6.1.4.1.8.1 1.1 0 .6-.2 2.1-.2 2.3 0 .4.2.8.6.6.4-.2 4.4-2.6 6.1-3.6 3.6-1.1 5.7-4.2 5.7-6.2z"/>
        </svg>
        <span>LINE</span>
    </a>

    <!-- Web Share / Copy Button -->
    <button type="button"
            onclick="shareStampippi('{{ addslashes($title) }}', '{{ addslashes($text) }}', '{{ addslashes($url) }}')"
            class="inline-flex items-center font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-lg transition shadow-xs {{ $btnClass }}"
            title="端末で共有またはURLをコピー">
        <span>🔗</span>
        <span>共有</span>
    </button>
</div>
