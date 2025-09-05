<?php // app/views/system/tenants/index.php

$datatable_options = [
    'serverSide' => true,
    'ajax' => ['url' => '/api/tenants/list', 'type' => 'POST'],
    'order' => [[3, 'desc']],
    'columns' => [
        dt_text_column('name', 'Tenant Name'),
        dt_text_column('domain', 'Domain'),
        dt_status_column('status', 'Status', [
            'active' => ['label' => 'Active', 'class' => 'badge bg-success-subtle text-success-emphasis'],
            'suspended' => ['label' => 'Suspended', 'class' => 'badge bg-warning-subtle text-warning-emphasis'],
            'disabled' => ['label' => 'Disabled', 'class' => 'badge bg-danger-subtle text-danger-emphasis'],
        ]),
        dt_datetime_column('created_at', 'Created On'),
        dt_actions_column([
            'edit' => ['icon' => 'pencil-outline', 'path' => '/tenants/%ID%/edit', 'title' => 'Edit Tenant'],
            'delete' => ['icon' => 'trash-outline', 'class' => 'text-danger', 'isButton' => true, 'title' => 'Delete Tenant']
        ])
    ]
];

// The page-specific script is now in its own file
$page_scripts = render_page_script(__DIR__ . '/_index_script.php');
?>

<div id="tenants-index-page">
    <?php
    $header_actions = '';
    // Only show the "Create Tenant" button to Super Admins
    if ($is_super_admin) {
        $header_actions = button('Create Tenant', [
            'variant' => 'primary',
            'icon' => 'add-outline',
            'href' => '/tenants/create'
        ]);
    }

    echo card([
        'header' => ['title' => 'All Tenants', 'actions' => $header_actions],
        'body' => render_datatable('tenants-table', $datatable_options),
        'attributes' => ['class' => 'card-body-flush']
    ]);
    ?>
</div>