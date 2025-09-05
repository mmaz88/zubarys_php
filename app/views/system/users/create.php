<?php // app/views/system/users/create.php

// Data needed for the form partial
$is_super_admin = session('is_app_admin');
$tenant_options = [];

if ($is_super_admin) {
    $tenants = table('tenants')->orderBy('name')->get();
    $tenant_options = array_column($tenants, 'name', 'id');
}

// Pass page-specific JS to the layout
$page_scripts = js('users/users-form.js');
?>

<!-- The SPA router uses this data-page-id to run the correct JS initializer -->
<div id="user-create-page-container" data-page-id="user-form">
    <?= card([
        'header' => [
            'title' => 'Create New User',
            'subtitle' => 'Fill in the details to add a new user to the system.'
        ],
        'body' => render_partial(__DIR__ . '/_form.php', [
            'is_super_admin' => $is_super_admin,
            'tenant_options' => $tenant_options,
            'form_action_url' => '/api/users' // API endpoint for creating
        ])
    ]) ?>
</div>