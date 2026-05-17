<?php
/**
 * Original SVG "food art" generator.
 * Self-developed multimedia: deterministic, category-themed illustrations
 * with optional CSS-animated steam — no external/lifted image assets.
 */

declare(strict_types=1);

function category_palette(string $icon): array
{
    return match ($icon) {
        'pizza'   => ['#E63329', '#F7B538'],
        'parmo'   => ['#C44536', '#F2C078'],
        'kebab'   => ['#B5651D', '#E8A04B'],
        'burger'  => ['#8A5A2B', '#E6B566'],
        'pasta'   => ['#D88C2E', '#F4D58D'],
        'chips'   => ['#D9A404', '#F6E27A'],
        'sides'   => ['#A7762F', '#E9C46A'],
        'wrap'    => ['#7A8C3A', '#C4D67A'],
        'dessert' => ['#7B4B94', '#D6A2E8'],
        'drink'   => ['#1E7C8C', '#7FD1DE'],
        'party'   => ['#E63329', '#F7B538'],
        default   => ['#E63329', '#F7B538'],
    };
}

function food_glyph(string $icon): string
{
    // Simple, original glyph paths centred in a 120x120 box.
    return match ($icon) {
        'pizza', 'party' => '<path d="M60 18 L104 96 Q60 112 16 96 Z" fill="#fff" opacity=".95"/>
            <circle cx="60" cy="56" r="7" fill="#E63329"/>
            <circle cx="44" cy="78" r="6" fill="#E63329"/>
            <circle cx="76" cy="78" r="6" fill="#2E7D32"/>',
        'parmo' => '<rect x="26" y="40" width="68" height="44" rx="10" fill="#fff"/>
            <path d="M26 60 q34 -22 68 0" stroke="#C44536" stroke-width="5" fill="none"/>
            <rect x="34" y="86" width="52" height="10" rx="3" fill="#F2C078"/>',
        'kebab', 'wrap' => '<path d="M44 22 q24 16 0 76 q-16 -38 0 -76Z" fill="#fff"/>
            <path d="M76 22 q24 16 0 76 q-16 -38 0 -76Z" fill="#fff" opacity=".85"/>',
        'burger' => '<rect x="28" y="36" width="64" height="20" rx="10" fill="#fff"/>
            <rect x="28" y="58" width="64" height="10" fill="#6FBF59"/>
            <rect x="28" y="68" width="64" height="12" fill="#8A5A2B"/>
            <rect x="28" y="82" width="64" height="14" rx="7" fill="#fff"/>',
        'pasta' => '<path d="M34 40 q26 -10 52 0 v6 q-26 36 -52 0Z" fill="#fff"/>
            <circle cx="50" cy="78" r="5" fill="#E63329"/>
            <circle cx="70" cy="80" r="5" fill="#E63329"/>',
        'chips' => '<g fill="#F6E27A">
            <rect x="40" y="34" width="7" height="56"/><rect x="52" y="30" width="7" height="60"/>
            <rect x="64" y="34" width="7" height="56"/><rect x="76" y="38" width="7" height="52"/></g>',
        'sides' => '<circle cx="60" cy="62" r="30" fill="#fff"/>
            <circle cx="60" cy="62" r="14" fill="#E9C46A"/>',
        'dessert' => '<path d="M40 50 a20 20 0 0 1 40 0Z" fill="#fff"/>
            <rect x="56" y="50" width="8" height="40" fill="#fff"/>
            <circle cx="60" cy="44" r="7" fill="#E63329"/>',
        'drink' => '<path d="M46 30 h28 l-5 64 h-18Z" fill="#fff"/>
            <rect x="58" y="16" width="4" height="20" fill="#fff"/>',
        default => '<circle cx="60" cy="60" r="34" fill="#fff"/>',
    };
}

/**
 * Inline SVG "photo" for a product or category.
 * $steam adds the self-developed CSS steam animation.
 */
function food_art(string $icon, string $label = '', bool $steam = false, string $class = 'food-art'): string
{
    [$c1, $c2] = category_palette($icon);
    $gid = 'g' . substr(md5($icon . $label), 0, 6);
    $glyph = food_glyph($icon);
    $steamSvg = $steam ? '
        <g class="steam" stroke="#fff" stroke-width="3" stroke-linecap="round" fill="none" opacity=".0">
          <path class="s1" d="M150 36 q8 -14 0 -28 q-8 -14 0 -28"/>
          <path class="s2" d="M170 36 q8 -14 0 -28 q-8 -14 0 -28"/>
          <path class="s3" d="M190 36 q8 -14 0 -28 q-8 -14 0 -28"/>
        </g>' : '';
    $cap = $label !== ''
        ? '<text x="160" y="190" text-anchor="middle" font-family="Inter,Arial,sans-serif"
             font-size="15" fill="#fff" opacity=".92">' . e($label) . '</text>'
        : '';

    return <<<SVG
    <svg class="{$class}" viewBox="0 0 320 210" role="img"
         aria-label="Illustration of {$label}" preserveAspectRatio="xMidYMid slice">
      <defs>
        <linearGradient id="{$gid}" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stop-color="{$c1}"/>
          <stop offset="1" stop-color="{$c2}"/>
        </linearGradient>
      </defs>
      <rect width="320" height="210" fill="url(#{$gid})"/>
      <circle cx="260" cy="40" r="70" fill="#fff" opacity=".06"/>
      <circle cx="40" cy="180" r="55" fill="#000" opacity=".05"/>
      {$steamSvg}
      <g transform="translate(100 38)">{$glyph}</g>
      {$cap}
    </svg>
    SVG;
}
