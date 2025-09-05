<?php // app/views/system/users/edit.php

// Data needed for the form partial
$is_super_admin = session('is_app_admin'); // <-- FIX: Define the variable
$tenant_options = [];

if ($is_super_admin) {
    $tenants = table('tenants')->orderBy('name')->get();
    $tenant_options = array_column($tenants, 'name', 'id');
}

// Pass page-specific JS to the layout
$page_scripts = js('users/users-form.js');

// The $user variable is passed from the route that renders this view
?>

<div id="user-edit-page-container" data-page-id="user-form" data-user-id="<?= h($user['id']) ?>">
    <?= card([
        'header' => [
            'title' => 'Edit User',
            'subtitle' => 'Editing profile for ' . h($user['name'])
        ],
        'body' => render_partial(__DIR__ . '/_form.php', [
            'user' => $user,
            'is_super_admin' => $is_super_admin, // <-- FIX: Pass the variable to the partial
            'tenant_options' => $tenant_options,
            'form_action_url' => '/api/users/' . h($user['id']) // API endpoint for updating
        ])
    ]) ?>
</div>