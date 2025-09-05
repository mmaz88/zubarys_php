<?php // app/views/system/users/_form.php
// REFACTORED: Multi-column layout using Bootstrap's grid system.
// CORRECTED: Tenant field is now conditional based on user role.

$is_edit = isset($user);
?>

<?= form_open([
    'id' => 'user-form',
    'action' => h($form_action_url ?? ''), // Action URL is now used by JS
    'method' => 'POST',
    'autocomplete' => 'off'
]); ?>

<!-- The old #form-errors div is no longer needed and has been removed. -->

<div class="row">
    <div class="col-md-6">
        <?= form_input('name', [
            'label' => 'Full Name',
            'required' => true,
            'value' => $user['name'] ?? '',
            'attributes' => ['id' => 'user-name', 'placeholder' => 'e.g., John Doe']
        ]); ?>
    </div>
    <div class="col-md-6">
        <?= form_input('email', [
            'type' => 'email',
            'label' => 'Email Address',
            'required' => true,
            'value' => $user['email'] ?? '',
            'attributes' => ['id' => 'user-email', 'placeholder' => 'e.g., john.doe@example.com']
        ]); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <?= form_password('password', [
            'label' => 'Password',
            'required' => !$is_edit,
            'help_text' => $is_edit ? 'Leave blank to keep current password.' : 'Min 8 characters.',
            'attributes' => ['id' => 'user-password', 'autocomplete' => 'new-password']
        ]); ?>
    </div>
    <div class="col-md-6">
        <?= form_password('password_confirmation', [
            'label' => 'Confirm Password',
            'required' => !$is_edit,
            'attributes' => ['id' => 'user-password-confirm', 'autocomplete' => 'new-password']
        ]); ?>
    </div>
</div>

<?php // This conditional check now works correctly because both parent views pass the variable. ?>
<?php if ($is_super_admin): ?>
    <div class="row">
        <div class="col-12">
            <?= form_select('tenant_id', [
                'label' => 'Tenant',
                'required' => true,
                'options' => $tenant_options ?? [],
                'selected' => $user['tenant_id'] ?? '',
                'placeholder' => 'Select a Tenant...'
            ]); ?>
        </div>
    </div>
<?php endif; ?>

<div class="form-toggle-group">
    <?= form_label('user-is-admin', 'Is Tenant Administrator?', ['class' => 'form-check-label']); ?>
    <?= form_checkbox('is_tenant_admin', [
        'wrapper_class' => 'form-switch',
        'checked' => (bool) ($user['is_tenant_admin'] ?? false),
        'attributes' => ['id' => 'user-is-admin'],
        'label' => ''
    ]); ?>
</div>

<div class="form-actions">
    <a href="/users" class="btn btn-secondary">Cancel</a>
    <?= form_submit($is_edit ? 'Update User' : 'Create User', [
        'attributes' => ['id' => 'save-user-btn']
    ]); ?>
</div>

<?= form_close(); ?>