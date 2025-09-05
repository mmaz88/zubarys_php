<?php
/**
 * app/views/system/permissions.php
 * A page for developers to manage application-wide permissions.
 */
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Form for Adding/Editing a Permission -->
    <div class="lg:col-span-1">
        <?= card([
            'header' => ['title' => 'Manage Permission', 'subtitle' => 'Create a new permission or select one to edit.'],
            'body' => '
                <form id="permission-form" class="form" autocomplete="off">
                    <input type="hidden" id="permission-id" name="id">
                    ' . form_input('slug', [
                    'label' => 'Slug (e.g., users.create)',
                    'required' => true,
                    'placeholder' => 'resource.action',
                    'help_text' => 'A unique, machine-readable key.',
                    'attributes' => ['id' => 'permission-slug']
                ]) . '
                    ' . form_textarea('description', [
                    'label' => 'Description',
                    'required' => true,
                    'rows' => 3,
                    'placeholder' => 'Controls the ability to create new users.',
                    'attributes' => ['id' => 'permission-description']
                ]) . '
                    <div class="form-actions">
                         ' . button('Clear', ['type' => 'button', 'variant' => 'secondary', 'attributes' => ['id' => 'clear-form-btn']]) . '
                         ' . button('Save Permission', ['type' => 'submit', 'variant' => 'primary', 'attributes' => ['id' => 'save-btn']]) . '
                    </div>
                </form>
            '
        ]); ?>
    </div>

    <!-- Right Column: Table of Existing Permissions -->
    <div class="lg:col-span-2">
        <?= card([
            'header' => ['title' => 'Existing Permissions'],
            'body' => '<div id="permissions-table-container"></div>',
            'attributes' => ['class' => 'card-body-flush'] // Removes padding around the table
        ]); ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Element Selectors ---
        const get = (id) => document.getElementById(id);
        const form = get('permission-form');
        const permissionIdInput = get('permission-id');
        const saveBtn = get('save-btn');
        const clearBtn = get('clear-form-btn');
        const tableContainer = get('permissions-table-container');

        // --- Core Functions ---
        const resetForm = () => {
            form.reset();
            permissionIdInput.value = '';
            saveBtn.textContent = 'Save Permission';
            get('permission-slug').focus();
        };

        const renderTable = (permissions) => {
            if (!permissions || permissions.length === 0) {
                tableContainer.innerHTML = '<p class="text-center p-6 text-fg-muted">No permissions found. Create one to get started.</p>';
                return;
            }

            const tableHtml = `
            <div class="table-wrapper">
                <table class="table table-hover">
                    <thead><tr>
                        <th class="p-3 text-left">Slug</th>
                        <th class="p-3 text-left">Description</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr></thead>
                    <tbody>
                    ${permissions.map(p => `
                        <tr>
                            <td class="p-3"><strong>${App.escapeHTML(p.slug)}</strong></td>
                            <td class="p-3">${App.escapeHTML(p.description)}</td>
                            <td class="p-3">
                                <div class="table-actions justify-end">
                                    <button class="btn btn-ghost btn-sm btn-icon edit-btn" data-id="${p.id}" title="Edit"><ion-icon name="pencil-outline"></ion-icon></button>
                                    <button class="btn btn-ghost btn-sm btn-icon text-destructive delete-btn" data-id="${p.id}" title="Delete"><ion-icon name="trash-outline"></ion-icon></button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                    </tbody>
                </table>
            </div>
        `;
            tableContainer.innerHTML = tableHtml;
        };

        const fetchAndRender = async () => {
            try {
                const response = await App.api('permissions');
                renderTable(response.data);
            } catch (error) {
                App.notify.error(error.message || 'Failed to fetch permissions.');
                tableContainer.innerHTML = `<p class="text-center p-6 text-destructive">${error.message}</p>`;
            }
        };

        const populateForm = (permission) => {
            permissionIdInput.value = permission.id;
            get('permission-slug').value = permission.slug;
            get('permission-description').value = permission.description;
            saveBtn.textContent = 'Update Permission';
        };

        // --- Event Listeners ---
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            saveBtn.classList.add('btn-loading');
            saveBtn.disabled = true;

            const id = permissionIdInput.value;
            const endpoint = id ? `permissions/${id}` : 'permissions';

            try {
                const response = await App.api(endpoint, { method: 'POST', body: new FormData(form) });
                App.notify.success(response.message);
                resetForm();
                await fetchAndRender();
            } catch (error) {
                if (error.status === 422 && error.response.errors) {
                    const messages = Object.values(error.response.errors).join('<br>');
                    App.notify.error(messages);
                } else {
                    App.notify.error(error.message || 'An error occurred.');
                }
            } finally {
                saveBtn.classList.remove('btn-loading');
                saveBtn.disabled = false;
            }
        });

        tableContainer.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                try {
                    const response = await App.api(`permissions/${editBtn.dataset.id}`);
                    populateForm(response.data);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } catch (error) { App.notify.error('Could not fetch permission details.'); }
            }

            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                try {
                    await App.confirm({
                        title: 'Delete Permission?',
                        message: 'This will remove the permission from all roles it is assigned to. This action cannot be undone.',
                        confirmVariant: 'destructive'
                    });
                    await App.api(`permissions/${deleteBtn.dataset.id}/delete`, { method: 'POST' });
                    App.notify.success('Permission deleted.');
                    if (permissionIdInput.value === deleteBtn.dataset.id) {
                        resetForm();
                    }
                    await fetchAndRender();
                } catch (error) {
                    if (error && error.message) App.notify.error(error.message);
                }
            }
        });

        clearBtn.addEventListener('click', resetForm);

        // --- Initial Load ---
        fetchAndRender();
    });
</script>