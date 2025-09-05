<?php
// FILE: app/views/dev/system_info.php

/**
 * A local helper function to render a full card for a stats section.
 * It intelligently handles different data types for display.
 */
function render_stats_card(string $title, string $icon, array $data, ?string $subtitle = null): string
{
    $body = '';

    // Handle special cases first
    if ($title === 'PHP Configuration' && isset($data['Loaded Extensions'])) {
        $extensions = $data['Loaded Extensions'];
        unset($data['Loaded Extensions']);
        $body .= '<dl class="stats-list">' . render_list_items($data) . '</dl>';
        $body .= '<h4 class="badge-list-title">Loaded Extensions (' . count($extensions) . ')</h4>';
        $body .= '<div class="badge-list">';
        foreach ($extensions as $ext) {
            $body .= '<span class="badge-item">' . h($ext) . '</span>';
        }
        $body .= '</div>';
    } elseif ($title === 'Composer Dependencies' && !empty($data)) {
        $body .= '<div class="badge-list">';
        foreach ($data as $name => $version) {
            $body .= '<span class="badge-item">' . h($name) . ' <span class="version">' . h($version) . '</span></span>';
        }
        $body .= '</div>';
    } else {
        // Default key-value list
        $body = '<dl class="stats-list">' . render_list_items($data) . '</dl>';
    }

    return card([
        'header' => ['title' => $title, 'subtitle' => $subtitle, 'actions' => icon_button($icon, ['type' => 'button', 'attributes' => ['disabled' => true]])],
        'body' => $body,
        'attributes' => ['style' => 'height: 100%;']
    ]);
}

/** Renders the <dt> and <dd> pairs for a list. */
function render_list_items(array $items): string
{
    $html = '';
    foreach ($items as $key => $value) {
        $html .= '<div class="stats-list-item">';
        $html .= '<dt>' . h($key) . '</dt>';
        $html .= '<dd>' . $value . '</dd>'; // Value is pre-formatted, so no h()
        $html .= '</div>';
    }
    return $html;
}
?>

<style>
    /* View-specific styles for clarity and organization */
    .stats-list {
        display: flex;
        flex-direction: column;
        gap: var(--space-3);
    }

    .stats-list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: var(--space-4);
        font-size: var(--font-size-sm);
        border-bottom: 1px solid rgb(var(--color-border-subtle));
        padding-bottom: var(--space-3);
    }

    .stats-list-item:last-child {
        border-bottom: none;
    }

    .stats-list-item dt {
        color: rgb(var(--color-fg-muted));
        white-space: nowrap;
    }

    .stats-list-item dd {
        font-weight: var(--font-weight-medium);
        color: rgb(var(--color-fg));
        text-align: right;
    }

    .badge-list-title {
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-semibold);
        text-transform: uppercase;
        color: rgb(var(--color-fg-muted));
        margin-top: var(--space-6);
        margin-bottom: var(--space-3);
        padding-bottom: var(--space-2);
        border-bottom: 1px solid rgb(var(--color-border-subtle));
    }

    .badge-list {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-2);
    }

    .badge-item {
        background-color: rgb(var(--color-bg-muted));
        color: rgb(var(--color-fg-subtle));
        padding: var(--space-1) var(--space-3);
        border-radius: var(--radius-full);
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-medium);
    }

    .badge-item .version {
        color: rgb(var(--color-fg));
        font-weight: var(--font-weight-normal);
    }
</style>

<div class="flex flex-col gap-6">
    <?php
    $cards = [];

    // Environment Card
    $cards[] = render_stats_card('Server Environment', 'hardware-chip-outline', $stats['environment']);

    // PHP Configuration Card
    $cards[] = render_stats_card('PHP Configuration', 'options-outline', $stats['php_config']);

    // Database Card
    $isDbOk = $stats['database']['Connection'] === 'Successful';
    $cards[] = render_stats_card(
        'Database',
        $isDbOk ? 'server-outline' : 'warning-outline',
        $stats['database'],
        $isDbOk ? null : 'Connection Failed'
    );

    // Filesystem Card
    $cards[] = render_stats_card('Filesystem', 'folder-open-outline', $stats['filesystem']);

    // OPcache Card
    if ($stats['opcache']['enabled']) {
        $opcache_stats = $stats['opcache'];
        unset($opcache_stats['enabled']);
        $cards[] = render_stats_card('OPcache Status', 'flash-outline', $opcache_stats, 'JIT Cache is Active');
    } else {
        $cards[] = card([
            'header' => ['title' => 'OPcache Status', 'actions' => icon_button('flash-off-outline', ['type' => 'button', 'attributes' => ['disabled' => true]])],
            'body' => '<p class="text-warning">' . h($stats['opcache']['message']) . '</p>',
            'variant' => 'outline'
        ]);
    }

    // Cache Card
    $cards[] = render_stats_card('File Cache', 'document-text-outline', $stats['cache']);

    // Render the grid of cards
    echo card_grid($cards, ['columns' => '1 lg:2 xl:3', 'gap' => '6']);

    // Composer Dependencies Card (full width)
    echo '<div class="lg:col-span-2 xl:col-span-3">';
    echo render_stats_card('Composer Dependencies', 'layers-outline', $stats['dependencies'], 'Packages loaded from composer.lock');
    echo '</div>';

    ?>
</div>