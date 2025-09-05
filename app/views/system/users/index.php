<?php // app/views/system/users/index.php

// 1. Define the DataTables configuration using the PHP helpers
$datatable_options = [
    'serverSide' => true,
    'processing' => true,
    'ajax' => [
        'url' => '/api/users/list',
        'type' => 'POST'
    ],
    'order' => [[3, 'desc']], // Default sort by "Created On" descending

    'columns' => [
        dt_text_column('name', 'User & Email', ['render' => 'userNameEmailRenderer']),
        dt_text_column('tenant_name', 'Tenant'),
        dt_boolean_column('is_tenant_admin', 'Tenant Admin'),
        dt_datetime_column('created_at', 'Created On'),
        dt_actions_column([
            // THE FIX: Add a 'view' action. Order determines display order.
            'view' => ['icon' => 'eye-outline', 'path' => '/users/%ID%', 'title' => 'View User'],
            'edit' => ['icon' => 'pencil-outline', 'path' => '/users/%ID%/edit', 'title' => 'Edit User'],
            'delete' => ['icon' => 'trash-outline', 'class' => 'text-danger', 'isButton' => true, 'title' => 'Delete User']
        ])
    ],
    'buttons' => [
        [
            'extend' => 'excelHtml5',
            'text' => '<ion-icon name="grid-outline" class="me-2"></ion-icon>Export to Excel',
            'className' => 'dropdown-item',
            'titleAttr' => 'Export to Excel (.xlsx)',
            'title' => 'Users Export - ' . date('Y-m-d'),
            'exportOptions' => ['columns' => [0, 1, 2, 3]]
        ],
        [
            'extend' => 'pdfHtml5',
            'text' => '<ion-icon name="document-text-outline" class="me-2"></ion-icon>Export to PDF',
            'className' => 'dropdown-item',
            'titleAttr' => 'Export to PDF',
            'title' => 'Users Export - ' . date('Y-m-d'),
            'exportOptions' => ['columns' => [0, 1, 2, 3]]
        ]
    ]
];

// 2. Render the page-specific JavaScript into a variable for the layout
$page_scripts = render_page_script(__DIR__ . '/_index_script.php');
?>

<div id="users-index-page-container">
    <?php
    $header_actions = '
    <div class="d-flex align-items-center gap-2">
        <!-- Export Dropdown Button -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="export-menu-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <ion-icon name="download-outline"></ion-icon>
                <span class="d-none d-sm-inline ms-2">Export</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="export-menu-btn" id="export-buttons-container">
                <!-- DataTables will insert buttons here -->
            </div>
        </div>
        <!-- Create User Button -->
        ' . button('Create User', [
            'variant' => 'primary',
            'icon' => 'add-outline',
            'href' => '/users/create'
        ]) . '
    </div>';

    echo card([
        'header' => ['title' => 'All Users', 'actions' => $header_actions],
        'body' => render_datatable('users-table', $datatable_options),
        'attributes' => ['class' => 'card-body-flush']
    ]);
    ?>
</div>