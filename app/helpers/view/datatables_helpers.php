<?php
// app/helpers/view/datatables_helpers.php


if (!function_exists('render_datatable')) {
    /**
     * Renders a DataTable container and its JSON configuration script.
     * This version is lean and expects the parent view to create the card layout.
     *
     * @param string $id The HTML ID for the table element.
     * @param array $options Custom options to merge with defaults.
     * @return string The HTML for the table and its configuration script.
     */
    function render_datatable(string $id, array $options = []): string
    {
        $defaultOptions = [
            'responsive' => true,
            'paging' => true,
            'pageLength' => 25,
            'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            'processing' => true,
            'stateSave' => true,
            'deferRender' => true,
            'orderCellsTop' => true,
            'language' => [
                'search' => '',
                'searchPlaceholder' => 'Search records...',
                'lengthMenu' => '_MENU_',
                'processing' => '<div></div>',
                'emptyTable' => '<div class="text-center py-5"><ion-icon name="document-text-outline" class="text-muted" style="font-size: 2.5rem;"></ion-icon><p class="mt-2 text-muted">No data available in table</p></div>',
                'zeroRecords' => '<div class="text-center py-5"><ion-icon name="search-circle-outline" class="text-muted" style="font-size: 2.5rem;"></ion-icon><p class="mt-2 text-muted">No matching records found</p></div>',
            ],
            'layout' => [
                'topStart' => 'pageLength',
                'topEnd' => 'search',
                'bottomStart' => 'info',
                'bottomEnd' => 'paging'
            ],
            'buttons' => null,
        ];

        $pageLayout = $options['layout'] ?? [];
        unset($options['layout']);

        $finalOptions = array_replace_recursive($defaultOptions, $options);
        $finalOptions['layout'] = array_merge($defaultOptions['layout'], $pageLayout);

        $html = sprintf(
            '<div class="dt-wrapper"><table id="%s" class="table dt-table table-hover" style="width:100%%" role="grid"></table></div>',
            htmlspecialchars($id)
        );

        $html .= sprintf(
            '<script type="application/json" id="%s-config" data-table-id="%s">%s</script>',
            htmlspecialchars($id),
            htmlspecialchars($id),
            json_encode($finalOptions, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE)
        );

        return $html;
    }
}


// =============================================================================
// COLUMN DEFINITION HELPERS (Strongly Typed & Documented)
// =============================================================================

if (!function_exists('dt_text_column')) {
    function dt_text_column(string $data, string $title, array $overrides = []): array
    {
        return array_merge(['data' => $data, 'title' => $title, 'type' => 'string'], $overrides);
    }
}

if (!function_exists('dt_number_column')) {
    function dt_number_column(string $data, string $title, array $overrides = []): array
    {
        $defaults = ['data' => $data, 'title' => $title, 'type' => 'num', 'className' => 'text-end', 'render' => 'numberRenderer'];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_currency_column')) {
    function dt_currency_column(string $data, string $title, string $currency = 'USD', array $overrides = []): array
    {
        $defaults = ['data' => $data, 'title' => $title, 'type' => 'num', 'className' => 'text-end', 'render' => 'currencyRenderer', 'currency' => $currency];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_date_column')) {
    function dt_date_column(string $data, string $title, array $overrides = []): array
    {
        $defaults = ['data' => $data, 'title' => $title, 'type' => 'date', 'render' => 'dateRenderer', 'className' => 'text-nowrap'];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_datetime_column')) {
    function dt_datetime_column(string $data, string $title, array $overrides = []): array
    {
        $defaults = ['data' => $data, 'title' => $title, 'type' => 'date', 'render' => 'datetimeRenderer', 'className' => 'text-nowrap'];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_boolean_column')) {
    function dt_boolean_column(string $data, string $title, array $overrides = []): array
    {
        $defaults = ['data' => $data, 'title' => $title, 'render' => 'booleanRenderer', 'className' => 'text-center', 'orderable' => true, 'searchable' => false];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_status_column')) {
    /**
     * @param array $statusMap ['value' => ['label' => 'Active', 'class' => 'badge bg-success']]
     */
    function dt_status_column(string $data, string $title, array $statusMap, array $overrides = []): array
    {
        $defaults = ['data' => $data, 'title' => $title, 'render' => 'statusRenderer', 'className' => 'text-center', 'statusMap' => $statusMap];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_user_column')) {
    function dt_user_column(string $nameData, string $emailData, ?string $avatarData = null, string $title = 'User', array $overrides = []): array
    {
        $defaults = ['data' => null, 'title' => $title, 'render' => 'userRenderer', 'orderable' => true, 'searchable' => true, 'nameField' => $nameData, 'emailField' => $emailData, 'avatarField' => $avatarData, 'className' => 'text-nowrap'];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_actions_column')) {
    /**
     * @param array $actions ['edit' => ['icon' => 'pencil', 'path' => '/users/%ID%', 'title' => 'Edit']]
     */
    function dt_actions_column(array $actions, string $title = 'Actions', array $overrides = []): array
    {
        $defaults = ['data' => null, 'title' => $title, 'orderable' => false, 'searchable' => false, 'render' => 'actionsRenderer', 'className' => 'text-center text-nowrap dt-actions-cell', 'actions' => $actions, 'width' => (count($actions) * 40) . 'px'];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_row_number_column')) {
    function dt_row_number_column(string $title = '#', array $overrides = []): array
    {
        $defaults = ['data' => null, 'title' => $title, 'orderable' => false, 'searchable' => false, 'render' => 'rowNumberRenderer', 'className' => 'text-center', 'width' => '40px'];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_checkbox_column')) {
    function dt_checkbox_column(array $overrides = []): array
    {
        $defaults = ['data' => null, 'orderable' => false, 'searchable' => false, 'className' => 'dt-center', 'title' => '<input type="checkbox" class="dt-select-checkbox select-all" aria-label="Select all rows">', 'defaultContent' => '<input type="checkbox" class="dt-select-checkbox select-row" aria-label="Select row">', 'width' => '30px'];
        return array_merge($defaults, $overrides);
    }
}

if (!function_exists('dt_progress_column')) {
    function dt_progress_column(string $data, string $title, array $overrides = []): array
    {
        $defaults = ['data' => $data, 'title' => $title, 'render' => 'progressRenderer', 'type' => 'num', 'className' => 'dt-center', 'width' => '120px'];
        return array_merge($defaults, $overrides);
    }
}

// =============================================================================
// CONFIGURATION HELPERS
// =============================================================================

if (!function_exists('dt_server_side_config')) {
    function dt_server_side_config(string $ajaxUrl, array $additionalOptions = []): array
    {
        $config = [
            'processing' => true,
            'serverSide' => true,
            'ajax' => ['url' => $ajaxUrl, 'type' => 'POST'],
            'searchDelay' => 450, // Debounce search requests
        ];
        return array_merge($config, $additionalOptions);
    }
}

if (!function_exists('dt_export_buttons')) {
    /**
     * Generates configuration for standard export buttons.
     */
    function dt_export_buttons(array $formats = ['copy', 'csv', 'excel', 'pdf', 'print']): array
    {
        $buttons = [];
        $iconMap = [
            'copy' => 'copy-outline',
            'csv' => 'document-text-outline',
            'excel' => 'grid-outline',
            'pdf' => 'document-outline',
            'print' => 'print-outline'
        ];

        foreach ($formats as $format) {
            $buttons[] = [
                'extend' => $format,
                'text' => "<ion-icon name='{$iconMap[$format]}' class='me-1'></ion-icon>" . ucfirst($format),
                'className' => 'btn btn-outline-secondary btn-sm',
                'exportOptions' => ['columns' => ':visible:not(.dt-actions-cell)'] // Exclude action columns
            ];
        }

        return $buttons;
    }
}