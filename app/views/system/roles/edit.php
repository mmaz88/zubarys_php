<?php // app/views/system/roles/edit.php

// Pass page-specific JS to the layout. The same script handles both create and edit.
$page_scripts = js('roles/roles-form.js');

// The $role variable is passed from the route that renders this view
?>

<div id="role-edit-page-container" data-role-id="<?= h($role['id']) ?>">
    <?= card([
        'header' => [
            'title' => 'Edit Role',
            'subtitle' => 'Editing role: ' . h($role['name'])
        ],
        'body' => render_partial(__DIR__ . '/_form.php', [
            'role' => $role,
            'is_super_admin' => session('is_app_admin'),
            'form_action_url' => '/api/roles/' . h($role['id'])
        ])
    ]) ?>
</div>