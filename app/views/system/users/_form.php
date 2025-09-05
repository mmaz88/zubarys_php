<?php
// app/views/system/users/_form.php

// This partial is used for both creating and editing users.
// We check if a $user object exists to determine the mode.
$is_edit = isset($user);
?>

<div class="row g-4">
    <!-- Left Column: User Details -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">User Details</h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="row">
                    <div class="col-md-6">
                        <?= form_input('name', [
                            'label' => 'Full Name',
                            'required' => true,
                            'value' => $user['name'] ?? ''
                        ]); ?>
                    </div>
                    <div class="col-md-6">
                        <?= form_input('email', [
                            'type' => 'email',
                            'label' => 'Email Address',
                            'required' => true,
                            'value' => $user['email'] ?? ''
                        ]); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <?= form_password('password', [
                            'label' => 'Password',
                            'required' => !$is_edit,
                            'help_text' => $is_edit ? 'Leave blank to keep current password.' : ''
                        ]); ?>
                    </div>
                    <div class="col-md-6">
                        <?= form_password('password_confirmation', [
                            'label' => 'Confirm Password',
                            'required' => !$is_edit
                        ]); ?>
                    </div>
                </div>

                <?php // The Tenant dropdown is only shown to Super Admins ?>
                <?php if ($is_super_admin): ?>
                    <?= form_select('tenant_id', [
                        'label' => 'Tenant',
                        'required' => true,
                        'options' => $tenant_options ?? [],
                        'selected' => $user['tenant_id'] ?? '',
                        'placeholder' => 'Select a Tenant...'
                    ]); ?>
                <?php endif; ?>

                <div class="form-toggle-group mt-auto">
                    <?= form_label('user-is-admin', 'Is Tenant Administrator?', ['class' => 'form-check-label']); ?>
                    <?= form_checkbox('is_tenant_admin', [
                        'wrapper_class' => 'form-switch',
                        'checked' => (bool) ($user['is_tenant_admin'] ?? false),
                        'attributes' => ['id' => 'user-is-admin'],
                        'label' => ''
                    ]); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Role Assignment -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Assign Roles</h5>
            </div>
            <div class="card-body d-flex flex-column">
                <!-- Dual Listbox Component -->
                <div class="dual-listbox">
                    <div class="dual-listbox-panel">
                        <div class="dual-listbox-header">Available Roles</div>
                        <div class="dual-listbox-search">
                            <input type="text" id="available-roles-search" class="form-input form-input-sm"
                                placeholder="Search...">
                        </div>
                        <ul id="available-roles-list" class="dual-listbox-list" multiple></ul>
                    </div>
                    <div class="dual-listbox-actions">
                        <button type="button" class="btn btn-sm btn-icon" id="add-role-btn"
                            title="Add Selected">&gt;</button>
                        <button type="button" class="btn btn-sm btn-icon" id="remove-role-btn"
                            title="Remove Selected">&lt;</button>
                        <button type="button" class="btn btn-sm btn-icon mt-2" id="add-all-roles-btn"
                            title="Add All">&gt;&gt;</button>
                        <button type="button" class="btn btn-sm btn-icon" id="remove-all-roles-btn"
                            title="Remove All">&lt;&lt;</button>
                    </div>
                    <div class="dual-listbox-panel">
                        <div class="dual-listbox-header">Assigned Roles</div>
                        <div class="dual-listbox-search">
                            <input type="text" id="assigned-roles-search" class="form-input form-input-sm"
                                placeholder="Search...">
                        </div>
                        <ul id="assigned-roles-list" class="dual-listbox-list" multiple></ul>
                    </div>
                </div>
                <!-- Hidden select to store assigned role IDs for form submission -->
                <select name="roles[]" id="assigned-roles-hidden" multiple class="d-none"></select>
            </div>
        </div>
    </div>
</div>