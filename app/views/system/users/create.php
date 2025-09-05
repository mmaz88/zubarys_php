<?php
// app/views/system/users/create.php

// The route provides: $is_super_admin, $tenant_options, $available_roles, $user_role_ids (which is empty)
$page_scripts = '<script type="application/json" id="role-assignment-data">' .
    json_encode([
        'available' => $available_roles,
        'assigned' => $user_role_ids // Will be an empty array
    ]) .
    '</script>' . js('users/role-assignment.js') . js('users/users-form.js');
?>

<div id="user-create-page-container">
    <?= form_open([
        'id' => 'user-form',
        'action' => '/api/users', // API endpoint for creating
        'method' => 'POST',
        'autocomplete' => 'off'
    ]); ?>

    <?php
    // Render the shared form partial with all necessary data
    echo render_partial(__DIR__ . '/_form.php', [
        'is_super_admin' => $is_super_admin,
        'tenant_options' => $tenant_options,
        'available_roles' => $available_roles,
        'user_role_ids' => $user_role_ids
    ]);
    ?>

    <div class="form-actions mt-4">
        <a href="/users" class="btn btn-secondary">Cancel</a>
        <?= form_submit('Create User', ['attributes' => ['id' => 'save-user-btn']]); ?>
    </div>

    <?= form_close(); ?>
</div>