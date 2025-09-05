<?php
// app/helpers/view/component_helpers.php
declare(strict_types=1);

//======================================================================
// CORE HTML HELPERS (Used by all components)
//======================================================================

if (!function_exists('h')) {
    /**
     * Escapes a string for secure output in HTML.
     */
    function h(string|array|object|null $string, int $flags = ENT_QUOTES, string $encoding = 'UTF-8', bool $double_encode = true): string
    {
        if ($string === null) {
            return '';
        }
        if (is_array($string) || is_object($string)) {
            return htmlspecialchars(json_encode($string), $flags, $encoding, $double_encode);
        }
        return htmlspecialchars((string) $string, $flags, $encoding, $double_encode);
    }
}

if (!function_exists('build_attributes')) {
    /**
     * Builds an HTML attributes string from an associative array.
     */
    function build_attributes(array $attributes): string
    {
        $html = [];
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html[] = $key;
                }
            } elseif ($value !== null) {
                $html[] = $key . '="' . h((string) $value) . '"';
            }
        }
        return implode(' ', $html);
    }
}


//======================================================================
// BUTTON HELPERS (from vw_buttons_helpers.php)
//======================================================================

if (!function_exists('button')) {
    /**
     * Renders a styled button or anchor tag.
     */
    function button(string $content, array $options = []): string
    {
        $defaults = [
            'variant' => 'primary',
            'size' => null,
            'href' => null,
            'icon' => null,
            'icon_position' => 'before',
            'disabled' => false,
            'loading' => false,
            'type' => null,
            'attributes' => [],
        ];
        $config = array_merge($defaults, $options);
        $tag = $config['href'] ? 'a' : 'button';
        $attributes = $config['attributes'];

        $variant = $config['variant'] === 'destructive' ? 'danger' : $config['variant'];
        $classes = ['btn', 'btn-' . $variant];
        if ($config['size'])
            $classes[] = 'btn-' . $config['size'];
        if ($config['icon'] && empty(trim(strip_tags($content))))
            $classes[] = 'btn-icon';
        if (!empty($attributes['class']))
            $classes[] = $attributes['class'];
        $attributes['class'] = implode(' ', array_unique(array_filter($classes)));

        if ($tag === 'button') {
            $attributes['type'] = $config['type'] ?? 'submit';
        } else {
            $attributes['href'] = $config['href'];
        }
        if ($config['disabled'] || $config['loading']) {
            $attributes['disabled'] = true;
            if ($tag === 'a')
                $attributes['aria-disabled'] = 'true';
        }

        $iconHtml = $config['icon'] ? '<ion-icon name="' . h($config['icon']) . '"></ion-icon>' : '';
        $contentHtml = trim($content) ? '<span>' . $content . '</span>' : '';

        if ($config['loading']) {
            $spinner = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ';
            $innerContent = $config['icon_position'] === 'before' ? $spinner . $contentHtml : $contentHtml . $spinner;
        } else {
            $innerContent = $config['icon_position'] === 'before' ? $iconHtml . $contentHtml : $contentHtml . $iconHtml;
        }
        return "<{$tag} " . build_attributes($attributes) . ">{$innerContent}</{$tag}>";
    }
}

if (!function_exists('icon_button')) {
    /**
     * Renders an icon-only button.
     */
    function icon_button(string $icon, array $options = []): string
    {
        $defaults = [
            'variant' => 'secondary',
            'type' => 'button',
            'attributes' => ['aria-label' => ucfirst(str_replace(['-', '_'], ' ', $icon))],
        ];
        $userAttributes = $options['attributes'] ?? [];
        $mergedAttributes = array_merge($defaults['attributes'], $userAttributes);
        $options['attributes'] = $mergedAttributes;
        $config = array_merge($defaults, $options);
        $config['icon'] = $icon;
        return button('', $config);
    }
}


//======================================================================
// CARD HELPERS (from vw_card_helpers.php)
//======================================================================

if (!function_exists('card')) {
    /**
     * Renders a styled card component.
     */
    function card(array $options = []): string
    {
        $header = $options['header'] ?? null;
        $body = $options['body'] ?? '';
        $footer = $options['footer'] ?? null;
        $attributes = $options['attributes'] ?? [];
        $classes = ['card'];
        if (!empty($attributes['class'])) {
            $classes[] = $attributes['class'];
        }
        $attributes['class'] = implode(' ', array_unique(array_filter($classes)));

        $html = '<div ' . build_attributes($attributes) . '>';
        if ($header) {
            $html .= '<div class="card-header">';
            if (!empty($header['title']) || !empty($header['subtitle'])) {
                $html .= '<div class="card-header-content">';
                if (!empty($header['title'])) {
                    $html .= '<h3 class="card-title">' . h($header['title']) . '</h3>';
                }
                if (!empty($header['subtitle'])) {
                    $html .= '<p class="card-subtitle">' . h($header['subtitle']) . '</p>';
                }
                $html .= '</div>';
            }
            if (!empty($header['actions'])) {
                $html .= '<div class="card-header-actions">' . $header['actions'] . '</div>';
            }
            $html .= '</div>';
        }

        $body_classes = 'card-body';
        if (strpos($attributes['class'], 'card-body-flush') !== false) {
            $body_classes .= ' p-0';
        }
        $html .= '<div class="' . $body_classes . '">' . $body . '</div>';

        if ($footer) {
            $html .= '<div class="card-footer">' . $footer . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('card_grid')) {
    /**
     * Renders a grid of cards.
     */
    function card_grid(array $cards, array $options = []): string
    {
        if (empty($cards))
            return '';
        $cols = $options['columns'] ?? '1';
        $gap = $options['gap'] ?? 4;
        $attributes = $options['attributes'] ?? [];
        $classes = ['row', "g-{$gap}"];

        $col_classes = [];
        $responsive_cols = explode(' ', $cols);
        foreach ($responsive_cols as $col_def) {
            if (strpos($col_def, '-') !== false) {
                [$bp, $num] = explode('-', $col_def);
                $col_classes[] = "row-cols-{$bp}-{$num}";
            } else {
                $col_classes[] = "row-cols-{$col_def}";
            }
        }
        $classes[] = implode(' ', $col_classes);

        if (!empty($attributes['class']))
            $classes[] = $attributes['class'];
        $attributes['class'] = implode(' ', array_unique(array_filter($classes)));

        $card_html = '';
        foreach ($cards as $card_content) {
            $card_html .= '<div class="col">' . $card_content . '</div>';
        }
        return "<div " . build_attributes($attributes) . ">" . $card_html . '</div>';
    }
}


//======================================================================
// MODAL HELPERS (from vw_modal_helpers.php)
//======================================================================

if (!function_exists('modal')) {
    /**
     * Renders a complete modal dialog component.
     */
    function modal(string $id, array $options): string
    {
        $title = $options['title'] ?? 'Modal Title';
        $body = $options['body'] ?? '';
        $footer = $options['footer'] ?? '';
        $sizeClass = !empty($options['size']) ? ' modal-' . $options['size'] : '';

        return '
        <div id="' . h($id) . '" class="modal-overlay hidden" aria-hidden="true">
            <div class="modal-content' . h($sizeClass) . '" role="dialog" aria-modal="true">
                <div class="modal-header">
                    <h3 id="' . h($id) . '-title" class="modal-title">' . h($title) . '</h3>
                    ' . icon_button('close-outline', ['attributes' => ['class' => 'modal-close', 'aria-label' => 'Close modal']]) . '
                </div>
                <div class="modal-body">
                    ' . $body . '
                </div>
                ' . ($footer ? '<div class="modal-footer">' . $footer . '</div>' : '') . '
            </div>
        </div>';
    }
}


//======================================================================
// ALERT & TOAST HELPERS (from alert_helpers.php)
//======================================================================

if (!function_exists('alert')) {
    /**
     * Prepare an alert to be sent to the modern alert system.
     */
    function alert(string $message, array $options = []): string
    {
        $config = [
            'message' => $message,
            'variant' => $options['variant'] ?? 'success',
            'title' => $options['title'] ?? null,
            'position' => $options['position'] ?? 'bottom-right',
            'duration' => $options['duration'] ?? 5000,
            'autoClose' => $options['autoClose'] ?? true,
        ];
        $config = array_filter($config, fn($value) => !is_null($value));
        return json_encode($config);
    }
}

if (!function_exists('toast')) {
    /**
     * Prepare a toast notification.
     */
    function toast(string $message, array $options = []): string
    {
        $defaults = [
            'position' => 'top-right',
            'duration' => 3000,
        ];
        return alert($message, array_merge($defaults, $options));
    }
}