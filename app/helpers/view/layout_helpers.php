<?php
// app/helpers/view/layout_helpers.php
declare(strict_types=1);

function get_current_path(): string
{
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
}

/**
 * Renders the main application sidebar.
 *
 * IMPROVEMENT: Decoupled hardcoded Bootstrap tooltip attributes. The menu item array
 * can now accept an 'attributes' key to pass in any custom HTML attributes, making
 * the component more reusable and StarterKit-agnostic.
 *
 * @param array $menu The navigation menu structure.
 * @param array $brand_config Configuration for the brand logo/name.
 * @return string The rendered HTML for the sidebar.
 */
function render_app_sidebar(array $menu, array $brand_config = []): string
{
    $current_path = get_current_path();
    $brand_name = h($brand_config['name'] ?? 'App');
    $brand_url = h($brand_config['url'] ?? '/');
    $brand_initial = mb_substr($brand_name, 0, 1);
    $nav_links_html = '';

    foreach ($menu as $item) {
        if (!empty($item['is_heading'])) {
            $nav_links_html .= '<li class="nav-heading">' . h($item['text']) . '</li>';
        } else {
            $url = '/' . ltrim($item['slug'] ?? '#', '/');
            $active_class = ($url === $current_path) ? ' active' : '';

            // Build custom attributes string
            $custom_attributes = build_attributes($item['attributes'] ?? []);

            $nav_links_html .= '<li>
                <a href="' . h($url) . '" class="nav-link' . $active_class . '" ' . $custom_attributes . '>
                    <ion-icon name="' . h($item['icon']) . '"></ion-icon>
                    <span>' . h($item['text']) . '</span>
                </a>
            </li>';
        }
    }

    return <<<HTML
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="{$brand_url}" class="brand-link">
                <div class="brand-icon">{$brand_initial}</div>
                <span class="brand-name">{$brand_name}</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <ul>{$nav_links_html}</ul>
        </nav>
        <div class="sidebar-footer">
            <button id="sidebar-toggle" class="sidebar-toggle-btn">
                <ion-icon name="chevron-back-outline" class="icon-collapse"></ion-icon>
                <ion-icon name="chevron-forward-outline" class="icon-expand"></ion-icon>
                <span>Collapse</span>
            </button>
        </div>
    </aside>
    HTML;
}


/**
 * Renders a partial view file within a secure scope.
 */
function render_partial(string $filePath, array $data = []): string
{
    if (!file_exists($filePath)) {
        trigger_error("Partial view file not found: " . htmlspecialchars($filePath), E_USER_WARNING);
        return '';
    }
    $render = function (string $file, array $params): string {
        ob_start();
        extract($params);
        include $file;
        return ob_get_clean() ?: '';
    };
    return $render($filePath, $data);
}

/**
 * Includes a script file, captures its output, and returns it as a string.
 */
function render_page_script(string $filePath): string
{
    if (!file_exists($filePath)) {
        trigger_error("Page script file not found: " . htmlspecialchars($filePath), E_USER_WARNING);
        return '';
    }
    ob_start();
    include $filePath;
    return ob_get_clean();
}

/**
 * Generates pagination links.
 *
 * IMPROVEMENT: This function's logic has been fully implemented.
 *
 * @param int $current_page The current active page.
 * @param int $total_pages The total number of pages.
 * @param string $base_url The base URL for pagination links.
 * @param int $show_pages The number of page links to show around the current page.
 * @return string The rendered HTML for pagination.
 */
function paginate(int $current_page, int $total_pages, string $base_url, int $show_pages = 5): string
{
    if ($total_pages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Pagination"><ul class="pagination">';
    $url_separator = str_contains($base_url, '?') ? '&' : '?';
    $base_url .= $url_separator . 'page=';

    // Previous button
    $disabled_prev = ($current_page <= 1) ? ' disabled' : '';
    $html .= '<li class="page-item' . $disabled_prev . '">';
    $html .= '<a class="page-link" href="' . ($current_page > 1 ? h($base_url . ($current_page - 1)) : '#') . '">Previous</a>';
    $html .= '</li>';

    // Page numbers
    $start = max(1, $current_page - floor($show_pages / 2));
    $end = min($total_pages, $start + $show_pages - 1);
    if ($end - $start + 1 < $show_pages) {
        $start = max(1, $end - $show_pages + 1);
    }

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . h($base_url . '1') . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '">';
        $html .= '<a class="page-link" href="' . h($base_url . $i) . '">' . $i . '</a>';
        $html .= '</li>';
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . h($base_url . $total_pages) . '">' . $total_pages . '</a></li>';
    }

    // Next button
    $disabled_next = ($current_page >= $total_pages) ? ' disabled' : '';
    $html .= '<li class="page-item' . $disabled_next . '">';
    $html .= '<a class="page-link" href="' . ($current_page < $total_pages ? h($base_url . ($current_page + 1)) : '#') . '">Next</a>';
    $html .= '</li>';

    $html .= '</ul></nav>';
    return $html;
}


/**
 * Generates a <meta> tag.
 */
function meta(string $name, string $content, string $type = 'name'): string
{
    return '<meta ' . $type . '="' . h($name) . '" content="' . h($content) . '">';
}

/**
 * Generates meta tags for Open Graph protocol.
 */
function og_tags(array $data): string
{
    $output = [];
    foreach ($data as $property => $content) {
        $output[] = meta('og:' . $property, $content, 'property');
    }
    return implode("\n", $output);
}

/**
 * Generates a <link> tag for a favicon.
 *
 * IMPROVEMENT: This function's logic has been fully implemented. It now handles
 * common icon types and provides a standard favicon link.
 *
 * @param string $file The path to the icon file (e.g., 'favicon.ico', 'logo.svg').
 * @param string|null $type The MIME type of the icon. Auto-detected for common types if null.
 * @return string The rendered HTML <link> tag.
 */
function favicon(string $file = 'favicon.ico', ?string $type = null): string
{
    if ($type === null) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $type = match ($extension) {
            'ico' => 'image/x-icon',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'gif' => 'image/gif',
            default => 'image/x-icon',
        };
    }
    return '<link rel="icon" type="' . h($type) . '" href="' . h(asset($file)) . '">';
}