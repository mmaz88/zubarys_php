<?php
/**
 * app/views/system/tenants.php
 * Final version with all helpers and dependencies corrected.
 */

// --- 1. Table Configuration ---
$table_config = [
    'columns' => [
        ['key' => 'name', 'title' => 'Name', 'sortable' => true, 'filterable' => true, 'filter_data_type' => 'text'],
        ['key' => 'domain', 'title' => 'Domain', 'sortable' => true, 'filterable' => true, 'filter_data_type' => 'text'],
        ['key' => 'status', 'title' => 'Status', 'sortable' => true, 'filterable' => true, 'filter_data_type' => 'text', 'render' => 'statusBadge'],
        ['key' => 'created_at', 'title' => 'Created On', 'sortable' => true, 'filterable' => true, 'filter_data_type' => 'date', 'render' => 'date'],
        ['key' => 'id', 'title' => 'Actions', 'sortable' => false, 'render' => 'actions', 'align' => 'right']
    ],
    'dataSource' => ['url' => '/tenants'],
    'filters' => [['key' => 'search', 'label' => 'Search name or domain...', 'type' => 'text', 'width' => 'grow']],
    'features' => ['inline_filters' => true, 'pagination' => true],
    'defaultSort' => ['column' => 'created_at', 'direction' => 'desc'],
    'emptyMessage' => 'No tenants found. Click "Create Tenant" to get started.'
];

// --- 2. Render Page Structure ---
$create_button = button('Create Tenant', [
    'variant' => 'primary',
    'icon' => 'add-outline',
    'attributes' => ['id' => 'create-tenant-btn']
]);

echo card([
    'header' => ['title' => 'All Tenants', 'actions' => $create_button],
    'body' => render_datatable('tenants-table', $table_config),
]);

// --- 3. Define and Render Modal ---
$modal_body = '
    <form id="tenant-form" autocomplete="off" class="space-y-4">
        <div id="modal-errors" class="hidden"></div>
        <input type="hidden" id="tenant-id" name="id">
        ' . form_input('name', ['label' => 'Tenant Name', 'required' => true, 'attributes' => ['id' => 'tenant-name']]) . '
        ' . form_input('domain', ['label' => 'Domain', 'help_text' => 'Optional. e.g., acme.example.com', 'attributes' => ['id' => 'tenant-domain']]) . '
        ' . form_select('status', [
        'label' => 'Status',
        'required' => true,
        'options' => ['active' => 'Active', 'suspended' => 'Suspended', 'disabled' => 'Disabled'],
        'selected' => 'active',
        'attributes' => ['id' => 'tenant-status']
    ]) . '
    </form>';

$modal_footer = button('Cancel', ['variant' => 'secondary', 'attributes' => ['class' => 'modal-close']]) .
    button('Save Tenant', ['type' => 'submit', 'variant' => 'primary', 'attributes' => ['id' => 'save-tenant-btn', 'form' => 'tenant-form']]);

echo modal('tenant-modal', [
    'title' => 'Create New Tenant',
    'body' => $modal_body,
    'footer' => $modal_footer
]);

?>

<!-- Page-Specific JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Custom Renderers for the Dynamic Table ---
        DynamicTable.addRenderer('statusBadge', (status) => {
            const lowerStatus = (status || '').toLowerCase();
            let colorClass = 'badge-secondary';
            if (lowerStatus === 'active') colorClass = 'badge-success';
            if (lowerStatus === 'suspended') colorClass = 'badge-warning';
            if (lowerStatus === 'disabled') colorClass = 'badge-destructive';
            const text = App.escapeHTML(lowerStatus.charAt(0).toUpperCase() + lowerStatus.slice(1));
            return `<span class="badge ${colorClass}">${text}</span>`;
        });

        DynamicTable.addRenderer('actions', (id) => {
            return `
            <div class="table-actions justify-end">
                <button class="btn btn-ghost btn-sm btn-icon edit-btn" data-id="${id}" title="Edit Tenant"><ion-icon name="pencil-outline"></ion-icon></button>
                <button class="btn btn-ghost btn-sm btn-icon text-destructive delete-btn" data-id="${id}" title="Delete Tenant"><ion-icon name="trash-outline"></ion-icon></button>
            </div>`;
        });

        // --- Initialize the table component ---
        DynamicTable.initAll();

        const tenantsTable = document.getElementById('tenants-table')?.dynamicTableInstance;
        if (!tenantsTable) {
            console.error('Tenants table instance not found.');
            return;
        }

        // --- Element Selectors & Modal Logic ---
        const get = (id) => document.getElementById(id);
        const modalEl = get('tenant-modal');
        const form = get('tenant-form');
        const saveBtn = get('save-tenant-btn');
        const errorContainer = get('modal-errors');

        const openModal = (mode = 'create', data = null) => {
            form.reset();
            get('tenant-id').value = '';
            errorContainer.classList.add('hidden');
            errorContainer.innerHTML = '';
            modalEl.querySelector('.modal-title').textContent = mode === 'edit' ? 'Edit Tenant' : 'Create New Tenant';

            if (mode === 'edit' && data) {
                get('tenant-id').value = data.id;
                get('tenant-name').value = data.name;
                get('tenant-domain').value = data.domain || '';
                get('tenant-status').value = data.status;
            }

            modalEl.classList.remove('hidden');
            setTimeout(() => modalEl.classList.add('visible'), 10);
        };

        const closeModal = () => {
            modalEl.classList.remove('visible');
            setTimeout(() => modalEl.classList.add('hidden'), 300);
        };

        const displayFormErrors = (errors) => {
            errorContainer.innerHTML = `<ul>${Object.values(errors).map(e => `<li>${App.escapeHTML(e)}</li>`).join('')}</ul>`;
            errorContainer.classList.remove('hidden');
            errorContainer.className = 'mb-4 p-3 bg-destructive/10 text-destructive border border-destructive/20 rounded-md text-sm';
        };

        // --- Event Listeners ---
        get('create-tenant-btn').addEventListener('click', () => openModal('create'));
        modalEl.querySelectorAll('.modal-close').forEach(el => el.addEventListener('click', closeModal));

        tenantsTable.element.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                try {
                    const result = await App.api(`tenants/${editBtn.dataset.id}`);
                    openModal('edit', result.data);
                } catch (error) {
                    App.notify.error(error.message || 'Failed to fetch tenant data.');
                }
            }

            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                try {
                    await App.confirm({ title: 'Delete Tenant?', message: 'This action cannot be undone.', confirmVariant: 'destructive' });
                    const result = await App.api(`tenants/${deleteBtn.dataset.id}/delete`, { method: 'POST' });
                    App.notify.success(result.message);
                    tenantsTable.refresh();
                } catch (error) {
                    if (error && error.message) App.notify.error(error.message);
                }
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            saveBtn.classList.add('btn-loading');
            saveBtn.disabled = true;
            const id = get('tenant-id').value;
            const endpoint = id ? `tenants/${id}` : 'tenants';

            try {
                const result = await App.api(endpoint, { method: 'POST', body: new FormData(form) });
                App.notify.success(result.message);
                closeModal();
                tenantsTable.refresh();
            } catch (error) {
                if (error.status === 422 && error.response.errors) {
                    displayFormErrors(error.response.errors);
                } else {
                    App.notify.error(error.message || 'An unexpected error occurred.');
                }
            } finally {
                saveBtn.classList.remove('btn-loading');
                saveBtn.disabled = false;
            }
        });
    });
</script>