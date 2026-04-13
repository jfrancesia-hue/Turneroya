<?php
/**
 * Logo marca TurneroYa. Acepta:
 *   $variant = 'dark' (default) | 'light'
 *   $size    = 'sm' | 'md' | 'lg'
 */
$variant = $variant ?? 'dark';
$size = $size ?? 'md';
$textColor = $variant === 'light' ? 'text-white' : 'text-ink-900';
$textSize = ['sm' => 'text-lg', 'md' => 'text-xl', 'lg' => 'text-2xl'][$size] ?? 'text-xl';
$iconSize = ['sm' => 'w-8 h-8', 'md' => 'w-9 h-9', 'lg' => 'w-11 h-11'][$size] ?? 'w-9 h-9';
?>
<a href="/" class="flex items-center gap-2.5 <?= $textColor ?> font-bold <?= $textSize ?> tracking-tight">
    <div class="<?= $iconSize ?> rounded-xl bg-gradient-to-br from-brand-600 via-brand-500 to-accent-500 flex items-center justify-center shadow-brand">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
    </div>
    Turnero<span class="text-brand-600">Ya</span>
</a>
