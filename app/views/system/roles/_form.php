<?php
// app/views/system/roles/_form.php
$is_edit = isset($role);

// Permissions data processing (remains the same)
$all_permissions = table('permissions')->orderBy('slug', 'ASC')->get();
$grouped_permissions = [];
foreach ($all_permissions as $permission) {
    $resource = ucfirst(explode('.', $permission['slug'])[0]);
    $grouped_permissions[$resource][] = $permission;
}
ksort($grouped_permissions);

// Tenant data processing (remains the same)
$tenants = $is_super_admin ? table('tenants')->orderBy('name')->get() : [];
$tenant_options = array_column($tenants, 'name', 'id');
?>

<?= form_open([
    'id' => 'role-form',
    'action' => h($form_action_url ?? ''),
    'method' => 'POST',
    'autocomplete' => 'off',
    'class' => 'needs-validation', // Added for Bootstrap validation styles
    'novalidate' => true
]); ?>

<div id="form-errors" class="alert alert-danger d-none"></div>

<div class="row g-5">
    <!-- Left Column: Role Details -->
    <div class="col-lg-4">
        <h5 class="mb-3">Role Details</h5>
        <p class="text-muted small">Provide a name and optional description for this role. For app administrators, you
            can also assign a scope.</p>
        <hr class="my-4">
        <?= form_input('name', [
            'label' => 'Role Name',
            'required' => true,
            'value' => $role['name'] ?? '',
            'attributes' => ['id' => 'role-name', 'placeholder' => 'e.g., Content Editor']
        ]); ?>

        <?php if ($is_super_admin): ?>
            <?= form_select('tenant_id', [
                'label' => 'Scope (Tenant)',
                'options' => $tenant_options,
                'selected' => $role['tenant_id'] ?? '',
                'placeholder' => 'Global (App-Level)'
            ]); ?>
        <?php endif; ?>

        <?= form_textarea('description', [
            'label' => 'Description',
            'rows' => 3,
            'value' => $role['description'] ?? '',
            'attributes' => ['id' => 'role-description', 'placeholder' => 'Briefly describe the purpose of this role.']
        ]); ?>
    </div>

    <!-- Right Column: Permissions -->
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Assign Permissions</h5>
                <p class="text-muted small mb-0">Select the permissions this role should have.</p>
            </div>
            <!-- Live Search Input -->
            <div class="w-50" style="max-width: 300px;">
                <?= form_input('permission_search', [
                    'wrapper' => false, // Render without the .form-group wrapper
                    'attributes' => [
                        'id' => 'permission-search-input',
                        'placeholder' => 'Search permissions...'
                    ]
                ]); ?>
            </div>
        </div>
        <hr class="my-4">

        <div id="permissions-grid" class="permission-grid">
            <?php if (empty($grouped_permissions)): ?>
                <div class="col-span-full text-center p-5 bg-light rounded">
                    <p class="text-muted m-0">No permissions have been defined in the system.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_permissions as $groupName => $permissions):
                    $groupId = str_slug($groupName);
                    $card_header_actions = form_checkbox('select_all', [
                        'label' => 'Select All',
                        'attributes' => ['class' => 'form-check-input select-all-group', 'data-group' => $groupId]
                    ]);

                    $card_body = '<div class="permission-group-body">';
                    foreach ($permissions as $p) {
                        $card_body .= form_checkbox('permissions[]', [
                            'label' => '<strong>' . h($p['slug']) . '</strong><br><small class="text-muted">' . h($p['description']) . '</small>',
                            'value' => $p['id'],
                            'wrapper_class' => 'permission-item',
                            'attributes' => ['data-group' => $groupId, 'class' => 'form-check-input permission-checkbox']
                        ]);
                    }
                    $card_body .= '</div>';

                    echo card([
                        'attributes' => ['class' => 'permission-group-card', 'data-search-term' => strtolower($groupName . implode(' ', array_column($permissions, 'slug')))],
                        'header' => ['title' => h($groupName), 'actions' => $card_header_actions],
                        'body' => $card_body
                    ]);
                endforeach; ?>
            <?php endif; ?>
        </div>
        <div id="no-permissions-found" class="text-center p-5 bg-light rounded" style="display: none;">
            <p class="text-muted m-0">No permissions found matching your search.</p>
        </div>
    </div>
</div>

<div class="form-actions mt-5">
    <a href="/roles" class="btn btn-secondary">Cancel</a>
    <?= form_submit($is_edit ? 'Update Role' : 'Create Role', [
        'attributes' => ['id' => 'save-role-btn']
    ]); ?>
</div>
<?= form_close(); ?>