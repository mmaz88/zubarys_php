<?php
// app/helpers/view/form_helpers.php
declare(strict_types=1);

/**
 * NEW: Opens a form tag and includes CSRF and method spoofing fields.
 *
 * WHY IT'S NEEDED: Standardizes form creation, making it secure by default by
 * automatically including CSRF protection. It simplifies creating forms for
 * PUT/PATCH/DELETE methods.
 *
 * @param array $options Form attributes (e.g., ['action' => '/submit', 'method' => 'POST', 'class' => 'my-form'])
 * @return string The opening <form> tag with hidden fields.
 */
function form_open(array $options = []): string
{
    $method = strtoupper($options['method'] ?? 'POST');
    $attributes = $options;

    // Spoof methods for PUT, PATCH, DELETE
    $spoofedMethod = in_array($method, ['PUT', 'PATCH', 'DELETE']);
    if ($spoofedMethod) {
        $attributes['method'] = 'POST';
    }

    $html = '<form ' . build_attributes($attributes) . '>';
    $html .= csrf_field();

    if ($spoofedMethod) {
        $html .= method_field($method);
    }

    return $html;
}

/**
 * NEW: Renders a closing </form> tag.
 *
 * WHY IT'S NEEDED: A semantic companion to form_open().
 *
 * @return string
 */
function form_close(): string
{
    return '</form>';
}

/**
 * NEW: Renders a label tag.
 *
 * WHY IT'S NEEDED: Provides a consistent way to create labels, especially
 * for custom form layouts where the label is separate from the input helper.
 *
 * @param string $for The 'for' attribute of the label.
 * @param string $text The label text.
 * @param array $attributes Additional HTML attributes.
 * @return string The HTML for the label.
 */
function form_label(string $for, string $text, array $attributes = []): string
{
    $defaultAttributes = ['for' => $for];
    // Avoid overriding the class if it's explicitly set to something else
    if (!isset($attributes['class'])) {
        $defaultAttributes['class'] = 'form-label';
    }

    $attrs = array_merge($defaultAttributes, $attributes);
    return '<label ' . build_attributes($attrs) . '>' . h($text) . '</label>';
}


/**
 * Renders a styled input field.
 */
function form_input(string $name, array $options = []): string
{
    $label = $options['label'] ?? '';
    $id = $options['attributes']['id'] ?? 'form-' . uniqid();
    $helpText = $options['help_text'] ?? null;
    $useWrapper = $options['wrapper'] ?? true;

    $attributes = array_merge([
        'type' => $options['type'] ?? 'text',
        'name' => $name,
        'id' => $id,
        'class' => 'form-input',
        'value' => $options['value'] ?? '',
        'placeholder' => $options['placeholder'] ?? ''
    ], $options['attributes'] ?? []);

    if (!empty($options['size'])) {
        $attributes['class'] .= ' form-input-' . $options['size'];
    }
    if ($options['required'] ?? false)
        $attributes['required'] = true;
    if ($options['disabled'] ?? false)
        $attributes['disabled'] = true;

    $html = '';
    if ($label) {
        $html .= '<label for="' . h($id) . '" class="form-label">' . h($label) . '</label>';
    }
    $html .= '<input ' . build_attributes($attributes) . '>';
    if ($helpText) {
        $html .= '<p class="form-help-text">' . h($helpText) . '</p>';
    }

    return $useWrapper ? '<div class="form-group">' . $html . '</div>' : $html;
}

/**
 * NEW: Renders a password input field.
 *
 * @param string $name The input name attribute.
 * @param array $options An array of options.
 * @return string The HTML for the password input field.
 */
function form_password(string $name, array $options = []): string
{
    $options['type'] = 'password';
    return form_input($name, $options);
}

/**
 * NEW: Renders a file input field.
 *
 * WHY IT'S NEEDED: File inputs have unique styling and behavior. This helper
 * standardizes their creation.
 *
 * @param string $name The input name attribute.
 * @param array $options An array of options.
 * @return string The HTML for the file input field.
 */
function form_file(string $name, array $options = []): string
{
    $options['type'] = 'file';
    // Add a specific class for file inputs if not already present
    $baseClass = $options['attributes']['class'] ?? '';
    if (strpos($baseClass, 'form-input-file') === false) {
        $options['attributes']['class'] = trim($baseClass . ' form-input-file');
    }

    // Unset options not applicable to file inputs before passing to form_input
    unset($options['value'], $options['placeholder']);

    return form_input($name, $options);
}

/**
 * NEW: Renders a textarea field.
 *
 * @param string $name The textarea name attribute.
 * @param array $options An array of options.
 * @return string The HTML for the textarea field.
 */
function form_textarea(string $name, array $options = []): string
{
    $label = $options['label'] ?? '';
    $id = $options['attributes']['id'] ?? 'form-textarea-' . uniqid();
    $helpText = $options['help_text'] ?? null;
    $useWrapper = $options['wrapper'] ?? true;
    $value = $options['value'] ?? '';

    $attributes = array_merge([
        'name' => $name,
        'id' => $id,
        'class' => 'form-textarea', // Custom class for styling
        'rows' => $options['rows'] ?? 3,
        'placeholder' => $options['placeholder'] ?? ''
    ], $options['attributes'] ?? []);

    if ($options['required'] ?? false)
        $attributes['required'] = true;
    if ($options['disabled'] ?? false)
        $attributes['disabled'] = true;

    $html = '';
    if ($label) {
        $html .= '<label for="' . h($id) . '" class="form-label">' . h($label) . '</label>';
    }
    $html .= '<textarea ' . build_attributes($attributes) . '>' . h($value) . '</textarea>';
    if ($helpText) {
        $html .= '<p class="form-help-text">' . h($helpText) . '</p>';
    }

    return $useWrapper ? '<div class="form-group">' . $html . '</div>' : $html;
}

/**
 * Renders a styled checkbox or a toggle switch.
 */
function form_checkbox(string $name, array $options = []): string
{
    $label = $options['label'] ?? '';
    $id = $options['attributes']['id'] ?? 'form-check-' . uniqid();
    $wrapperClasses = ['form-check'];
    if (!empty($options['wrapper_class'])) {
        $wrapperClasses[] = $options['wrapper_class'];
    }

    $attributes = array_merge([
        'type' => 'checkbox',
        'name' => $name,
        'id' => $id,
        'class' => 'form-check-input', // CORRECTED: Use standard class name
        'value' => $options['value'] ?? '1',
    ], $options['attributes'] ?? []);

    if ($options['checked'] ?? false)
        $attributes['checked'] = true;
    if ($options['disabled'] ?? false)
        $attributes['disabled'] = true;
    // Add role="switch" for accessibility if it's a switch
    if (str_contains($options['wrapper_class'] ?? '', 'form-switch')) {
        $attributes['role'] = 'switch';
    }

    $html = '<div class="' . implode(' ', $wrapperClasses) . '">';
    $html .= '<input ' . build_attributes($attributes) . '>';
    if ($label) {
        $html .= '<label for="' . h($id) . '" class="form-check-label">' . $label . '</label>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * NEW: Renders a set of radio buttons.
 *
 * @param string $name The name attribute for the radio group.
 * @param array $options An array of options.
 * @return string The HTML for the radio buttons.
 */
function form_radio(string $name, array $options = []): string
{
    $label = $options['label'] ?? '';
    $radioOptions = $options['options'] ?? [];
    $checkedValue = $options['checked'] ?? '';
    $useWrapper = $options['wrapper'] ?? true;

    $radiosHtml = '';
    foreach ($radioOptions as $value => $optionLabel) {
        $id = 'form-radio-' . uniqid();
        $attributes = [
            'type' => 'radio',
            'name' => $name,
            'id' => $id,
            'class' => 'form-radio',
            'value' => $value,
        ];
        if ($value == $checkedValue) {
            $attributes['checked'] = true;
        }
        if ($options['disabled'] ?? false) {
            $attributes['disabled'] = true;
        }

        $radiosHtml .= '<div class="form-radio-item">';
        $radiosHtml .= '<input ' . build_attributes($attributes) . '>';
        $radiosHtml .= '<label for="' . h($id) . '" class="form-radio-label">' . h($optionLabel) . '</label>';
        $radiosHtml .= '</div>';
    }

    $html = '';
    if ($label) {
        $html .= '<p class="form-label">' . h($label) . '</p>'; // Use a p or div for the group label
    }
    $html .= '<div class="form-radio-group">' . $radiosHtml . '</div>';

    return $useWrapper ? '<div class="form-group">' . $html . '</div>' : $html;
}

/**
 * Renders a styled select dropdown.
 */
function form_select(string $name, array $options = []): string
{
    $label = $options['label'] ?? '';
    $selectOptions = $options['options'] ?? [];
    $selected = $options['selected'] ?? '';
    $placeholder = $options['placeholder'] ?? '';
    $id = $options['attributes']['id'] ?? 'form-select-' . uniqid();
    $useWrapper = $options['wrapper'] ?? true;

    $attributes = array_merge([
        'name' => $name,
        'id' => $id,
        'class' => 'form-select',
    ], $options['attributes'] ?? []);

    if (!empty($options['size'])) {
        $attributes['class'] .= ' form-select-' . $options['size'];
    }
    if ($options['required'] ?? false)
        $attributes['required'] = true;
    if ($options['disabled'] ?? false)
        $attributes['disabled'] = true;

    $optionsHtml = '';
    if ($placeholder !== '') {
        $optionsHtml .= '<option value="" ' . (empty($selected) ? 'selected' : '') . ' disabled>' . h($placeholder) . '</option>';
    }

    foreach ($selectOptions as $value => $optionLabel) {
        $isSelected = ($value == $selected);
        $optionsHtml .= '<option value="' . h($value) . '"' . ($isSelected ? ' selected' : '') . '>' . h($optionLabel) . '</option>';
    }

    $html = '';
    if ($label) {
        $html .= '<label for="' . h($id) . '" class="form-label">' . h($label) . '</label>';
    }
    $html .= '<select ' . build_attributes($attributes) . '>' . $optionsHtml . '</select>';

    return $useWrapper ? '<div class="form-group">' . $html . '</div>' : $html;
}

/**
 * NEW: Renders a submit button.
 *
 * WHY IT'S NEEDED: Provides a consistent, form-specific way to create
 * submit buttons, abstracting the general-purpose `button()` component helper.
 *
 * @param string $text The button's text content.
 * @param array $options An array of options for the `button()` helper.
 * @return string The HTML for the submit button.
 */
function form_submit(string $text = 'Submit', array $options = []): string
{
    if (!function_exists('button')) {
        // Fallback if the component helper isn't loaded
        return '<button type="submit" class="btn btn-primary">' . h($text) . '</button>';
    }

    $defaults = [
        'type' => 'submit',
        'variant' => 'primary',
    ];

    $config = array_merge($defaults, $options);
    return button($text, $config);
}


/**
 * Generates a hidden input field.
 */
function hidden_input(string $name, string $value): string
{
    return '<input type="hidden" name="' . h($name) . '" value="' . h($value) . '">';
}

/**
 * Generates a hidden input field for CSRF protection.
 */
function csrf_field(): string
{
    return hidden_input('_csrf_token', csrf_token());
}

/**
 * Generates a hidden input field to override the form method (e.g., for PUT or DELETE).
 */
function method_field(string $method): string
{
    return hidden_input('_method', strtoupper($method));
}


function form_row_open(array $options = []): string
{
    $attributes = array_merge(['class' => 'row g-4'], $options);
    return '<div ' . build_attributes($attributes) . '>';
}

function form_row_close(): string
{
    return '</div>';
}