<?php
// app/views/system/roles/create.php

// Pass page-specific JS to the layout
$page_scripts = js('roles/roles-form.js');
?>

<div id="role-create-page-container">
    <?= card([
        'header' => [
            'title' => 'Create New Role',
            'subtitle' => 'Define a new role and assign its permissions.'
        ],
        'body' => render_partial(__DIR__ . '/_form.php', [
            'is_super_admin' => session('is_app_admin'),
            // ===== THE FIX IS HERE =====
            // The form must submit to the dedicated 'create' endpoint.
            'form_action_url' => '/api/roles/create'
            // ===== END OF FIX =====
        ])
    ]) ?>
</div>