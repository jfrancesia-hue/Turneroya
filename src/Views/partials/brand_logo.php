<?php
/**
 * Logo marca Reservia. Acepta:
 *   $variant = 'dark' (default) | 'light'
 *   $size    = 'sm' | 'md' | 'lg'
 */
$variant = $variant ?? 'dark';
$size = $size ?? 'md';
$textColor = $variant === 'light' ? 'text-white' : 'text-ink-900';
$textSize = ['sm' => 'text-lg', 'md' => 'text-xl', 'lg' => 'text-2xl'][$size] ?? 'text-xl';
$markSize = ['sm' => 'brand-mark-sm', 'md' => 'brand-mark-md', 'lg' => 'brand-mark-lg'][$size] ?? 'brand-mark-md';
?>
<a href="/" class="brand-logo <?= $textColor ?> <?= $textSize ?>">
    <span class="brand-mark <?= $markSize ?>" aria-hidden="true">
        <svg viewBox="0 0 48 48" fill="none">
            <path class="brand-bubble" d="M11 8h26c5 0 8 3 8 8v14c0 5-3 8-8 8H24L12 45v-7h-1c-5 0-8-3-8-8V16c0-5 3-8 8-8Z"/>
            <path class="brand-page" d="M15 15h18a4 4 0 0 1 4 4v12a4 4 0 0 1-4 4H15a4 4 0 0 1-4-4V19a4 4 0 0 1 4-4Z"/>
            <path class="brand-line" d="M16 22h16"/>
            <path class="brand-check" d="m17 28 5 5 11-13"/>
        </svg>
    </span>
    <span>Reserv<span class="brand-word-accent">ia</span></span>
</a>
