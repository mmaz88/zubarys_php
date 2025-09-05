<?php
// app/views/system/users/edit.php

// The route provides: $user, $is_super_admin, $tenant_options, $available_roles, $user_role_ids
$page_scripts = '<script type="application/json" id="role-assignment-data">' .
    json_encode([
        'available' => $available_roles,
        'assigned' => $user_role_ids
    ]) .
    '</script>' . js('users/role-assignment.js') . js('users/users-form.js');
?>

<div id="user-edit-page-container" data-user-id="<?= h($user['id']) ?>">
     <?= form_open([
        'id' => 'user-form',
        'action' => '/api/users/' . h($user['id']), // API endpoint for updating
        'method' => 'POST',
        'autocomplete' => 'off'
    ]); ?>

    <?php
    // Render the shared form partial with all necessary data
    echo render_partial(__DIR__ . '/_form.php', [
        'user' => $user,
        'is_super_admin' => $is_super_admin,
        'tenant_options' => $tenant_options,
        'available_roles' => $available_roles,
        'user_role_ids' => $user_role_ids
    ]);
    ?>

    <div class="form-actions mt-4">
        <a href="/users" class="btn btn-secondary">Cancel</a>
        <?= form_submit('Update User', ['attributes' => ['id' => 'save-user-btn']]); ?>
    </div>
    
    <?= form_close(); ?>
</div>