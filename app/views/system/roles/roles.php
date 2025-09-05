<?php
/**
 * app/views/system/roles.php
 * REWRITTEN: A modern, table-based interface for managing global and tenant roles.
 */

// --- 1. Dynamic Table Configuration ---
$table_config = [
    'title' => 'Roles & Permissions',
    'description' => 'Manage global and tenant-specific user roles.',
    'columns' => [
        ['key' => 'name', 'title' => 'Role Name', 'sortable' => true, 'render' => 'roleName'],
        ['key' => 'tenant_name', 'title' => 'Scope', 'sortable' => true, 'render' => 'scope'],
        ['key' => 'user_count', 'title' => 'Users', 'sortable' => true, 'align' => 'center'],
        ['key' => 'id', 'title' => 'Actions', 'sortable' => false, 'render' => 'actions', 'align' => 'right']
    ],
    'dataSource' => ['url' => '/roles'],
    'features' => [
        'global_search' => false, // No search bar for this simpler table
        'pagination' => false // Roles list is unlikely to be very long
    ],
    'defaultSort' => ['column' => 'name', 'direction' => 'asc'],
    'emptyMessage' => 'No roles found. Click "Create Role" to get started.'
];

// --- 2. Page Setup ---
$create_button = button('Create Role', [
    'variant' => 'primary',
    'icon' => 'add-outline',
    'attributes' => ['id' => 'create-role-btn']
]);

echo card([
    'header' => [
        'title' => 'All Roles',
        'actions' => $create_button
    ],
    'body' => render_datatable('roles-table', $table_config),
    'attributes' => ['class' => 'card-body-flush']
]);

// --- 3. Data for Modal ---
$all_permissions = table('permissions')->orderBy('slug', 'ASC')->get();
$grouped_permissions = [];
foreach ($all_permissions as $permission) {
    $parts = explode('.', $permission['slug'], 2);
    $resource = ucfirst($parts[0] ?? 'General');
    $grouped_permissions[$resource][] = $permission;
}
ksort($grouped_permissions);

$is_super_admin = session('is_app_admin');
$tenants = $is_super_admin ? table('tenants')->orderBy('name')->get() : [];
$tenant_options = $is_super_admin ? array_column($tenants, 'name', 'id') : [];
?>

<!-- =================================================================
     Role Create/Edit Modal
================================================================== -->
<div id="role-modal" class="modal-overlay hidden">
    <div class="modal-content modal-lg">
        <form id="role-form" autocomplete="off">
            <div class="modal-header">
                <h3 id="modal-title" class="modal-title">Create New Role</h3>
                <?= icon_button('close-outline', ['attributes' => ['class' => 'modal-close']]) ?>
            </div>

            <div class="modal-body">
                <div id="modal-errors"
                    class="hidden mb-4 p-3 bg-destructive/10 text-destructive border border-destructive/20 rounded-md text-sm">
                </div>
                <input type="hidden" id="role-id" name="id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?= form_input('name', ['label' => 'Role Name', 'required' => true, 'attributes' => ['id' => 'role-name']]) ?>

                    <?php if ($is_super_admin): ?>
                        <?= form_select('tenant_id', [
                            'label' => 'Scope (Tenant)',
                            'options' => $tenant_options,
                            'placeholder' => 'App-Level (Global)',
                            'attributes' => ['id' => 'role-tenant']
                        ]) ?>
                    <?php endif; ?>
                </div>

                <?= form_textarea('description', ['label' => 'Description', 'rows' => 2, 'attributes' => ['id' => 'role-description']]) ?>

                <div class="form-group mt-4">
                    <label class="form-label">Permissions</label>
                    <div id="permissions-list"
                        class="max-h-80 overflow-y-auto p-3 border border-border rounded-md bg-bg-subtle space-y-4">
                        <?php if (empty($grouped_permissions)): ?>
                            <p class="text-sm text-fg-muted">No permissions defined.</p>
                        <?php else: ?>
                            <?php foreach ($grouped_permissions as $groupName => $permissions):
                                $groupSlug = strtolower(str_replace(' ', '-', $groupName)); ?>
                                <div class="permission-group border border-border-subtle rounded-md p-3 bg-bg">
                                    <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                                        <h4 class="font-semibold text-sm"><?= h($groupName) ?></h4>
                                        <?= form_checkbox('select_group', ['label' => 'Select All', 'attributes' => ['class' => 'select-group-permissions', 'data-group' => $groupSlug]]) ?>
                                    </div>
                                    <div class="pt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <?php foreach ($permissions as $permission): ?>
                                            <?= form_checkbox('permissions[]', [
                                                'label' => '<div class="flex flex-col"><strong class="font-medium text-sm">' . h($permission['slug']) . '</strong><span class="text-xs text-fg-muted">' . h($permission['description']) . '</span></div>',
                                                'value' => $permission['id'],
                                                'attributes' => ['class' => 'permission-checkbox', 'data-group' => $groupSlug]
                                            ]) ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-between">
                <div>
                    <button type="button" id="delete-role-btn" class="btn btn-destructive hidden">Delete</button>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <?= button('Save Role', ['type' => 'submit', 'variant' => 'primary', 'attributes' => ['id' => 'save-role-btn']]) ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- =================================================================
     Page-Specific JavaScript
================================================================== -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Custom Renderers ---
        DynamicTable.addRenderer('roleName', (value, row) => {
            const name = App.escapeHTML(row.name);
            const desc = App.escapeHTML(row.description);
            return `<div class="font-medium text-fg">${name}</div><div class="text-xs text-fg-muted">${desc}</div>`;
        });

        DynamicTable.addRenderer('scope', (value, row) => {
            if (!row.tenant_id) {
                return `<span class="badge badge-info">Global</span>`;
            }
            return `<span class="badge badge-secondary">${App.escapeHTML(value || 'Tenant')}</span>`;
        });

        DynamicTable.addRenderer('actions', (id, row) => {
            // Tenant admins cannot edit or delete global roles
            const isSuperAdmin = <?= json_encode(session('is_app_admin')) ?>;
            const canManage = isSuperAdmin || row.tenant_id !== null;
            if (!canManage) return '<div class="text-right pr-4 text-xs text-fg-muted">-</div>';

            return `
            <div class="table-actions justify-end">
                <button class="btn btn-ghost btn-sm btn-icon edit-btn" data-id="${id}" title="Edit Role"><ion-icon name="pencil-outline"></ion-icon></button>
                <button class="btn btn-ghost btn-sm btn-icon text-destructive delete-btn" data-id="${id}" title="Delete Role"><ion-icon name="trash-outline"></ion-icon></button>
            </div>`;
        });

        DynamicTable.initAll();

        // --- Modal & Form Logic ---
        const rolesTable = document.getElementById('roles-table')?.dynamicTableInstance;
        if (!rolesTable) return;

        const get = (id) => document.getElementById(id);
        const modal = get('role-modal');
        const form = get('role-form');
        const saveBtn = get('save-role-btn');
        const deleteBtn = get('delete-role-btn');
        const errorContainer = get('modal-errors');
        const permissionsList = get('permissions-list');
        let activeModalCloser = null;


        const updateGroupCheckboxes = () => {
            permissionsList.querySelectorAll('.select-group-permissions').forEach(groupCheckbox => {
                const group = groupCheckbox.dataset.group;
                const inGroup = permissionsList.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
                const checkedInGroup = permissionsList.querySelectorAll(`.permission-checkbox[data-group="${group}"]:checked`);
                groupCheckbox.checked = inGroup.length > 0 && inGroup.length === checkedInGroup.length;
                groupCheckbox.indeterminate = checkedInGroup.length > 0 && checkedInGroup.length < inGroup.length;
            });
        };

        const openModal = (mode = 'create', data = null) => {
            form.reset();
            get('role-id').value = '';
            errorContainer.classList.add('hidden');
            get('modal-title').textContent = mode === 'edit' ? 'Edit Role' : 'Create New Role';
            deleteBtn.classList.add('hidden');

            form.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
            updateGroupCheckboxes();

            if (mode === 'edit' && data) {
                get('role-id').value = data.id;
                get('role-name').value = data.name;
                get('role-description').value = data.description || '';
                if (get('role-tenant')) {
                    get('role-tenant').value = data.tenant_id || '';
                }
                if (data.permissions && Array.isArray(data.permissions)) {
                    form.querySelectorAll('.permission-checkbox').forEach(cb => {
                        cb.checked = data.permissions.includes(parseInt(cb.value));
                    });
                }
                updateGroupCheckboxes();
                deleteBtn.classList.remove('hidden');
            }

            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.add('visible'), 10);

            const handleEsc = (e) => {
                if (e.key === 'Escape') closeModal();
            };
            document.addEventListener('keydown', handleEsc);
            activeModalCloser = () => document.removeEventListener('keydown', handleEsc);
        };

        const closeModal = () => {
            modal.classList.remove('visible');
            setTimeout(() => modal.classList.add('hidden'), 200);
            if (activeModalCloser) activeModalCloser();
        };

        // --- Event Listeners ---
        get('create-role-btn').addEventListener('click', () => openModal('create'));
        modal.querySelectorAll('.modal-close').forEach(el => el.addEventListener('click', closeModal));

        rolesTable.element.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                try {
                    const result = await App.api(`roles/${editBtn.dataset.id}`);
                    openModal('edit', result.data);
                } catch (error) {
                    App.notify.error(error.message || 'Failed to fetch role data.');
                }
            }

            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                try {
                    await App.confirm({ title: 'Delete Role?', message: 'Are you sure? This action cannot be undone.', confirmVariant: 'destructive' });
                    const result = await App.api(`roles/${deleteBtn.dataset.id}/delete`, { method: 'POST' });
                    App.notify.success(result.message);
                    rolesTable.refresh();
                } catch (error) {
                    if (error && error.message) App.notify.error(error.message);
                }
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            saveBtn.classList.add('btn-loading');
            saveBtn.disabled = true;
            const id = get('role-id').value;
            const endpoint = id ? `roles/${id}` : 'roles';
            try {
                const result = await App.api(endpoint, { method: 'POST', body: new FormData(form) });
                App.notify.success(result.message);
                closeModal();
                rolesTable.refresh();
            } catch (error) {
                if (error.status === 422 && error.response.errors) {
                    const messages = Object.values(error.response.errors).join('<br>');
                    errorContainer.innerHTML = messages;
                    errorContainer.classList.remove('hidden');
                } else {
                    App.notify.error(error.message || 'An unexpected error occurred.');
                }
            } finally {
                saveBtn.classList.remove('btn-loading');
                saveBtn.disabled = false;
            }
        });

        deleteBtn.addEventListener('click', async () => {
            const id = get('role-id').value;
            if (!id) return;
            try {
                await App.confirm({ title: 'Delete Role?', message: 'Are you sure? This cannot be undone.', confirmVariant: 'destructive' });
                const result = await App.api(`roles/${id}/delete`, { method: 'POST' });
                App.notify.success(result.message);
                closeModal();
                rolesTable.refresh();
            } catch (error) {
                if (error && error.message) App.notify.error(error.message);
            }
        });

        permissionsList.addEventListener('change', (e) => {
            if (e.target.classList.contains('select-group-permissions')) {
                const group = e.target.dataset.group;
                permissionsList.querySelectorAll(`.permission-checkbox[data-group="${group}"]`).forEach(cb => {
                    cb.checked = e.target.checked;
                });
            }
            updateGroupCheckboxes();
        });
    });
</script>