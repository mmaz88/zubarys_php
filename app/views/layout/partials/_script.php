<!-- app/views/system/users/_userScript.php -->
<script>
(() => {
    /**
     * Main setup function for the Users page. Called by the SPA router.
     * @returns {Function} A cleanup function to be called on page navigation.
     */
    const initializeUsersPage = () => {
        const gridId = 'users-grid';
        const configId = 'users-grid-config';
        const modalEl = document.getElementById('user-modal');
        const form = document.getElementById('user-form');
        const saveBtn = document.getElementById('save-user-btn');
        const createBtn = document.getElementById('create-user-btn');
        const getEl = (id) => document.getElementById(id);

        const gridHelper = new AgGridHelper(gridId, configId);
        let userModal = null;
        let gridApi = null;

        try {
            // Because the HTML is now at the root, `rootElement` is not strictly necessary,
            // but it's a good safeguard to keep.
            userModal = new bootstrap.Modal(modalEl, { rootElement: document.body });

            gridHelper.gridOptions.components = {
                ...gridHelper.gridOptions.components,
                userNameEmailRenderer: p => p.data ? `<div class="fw-medium">${App.escapeHTML(p.data.name || '')}</div><div class="text-muted small">${App.escapeHTML(p.data.email || '')}</div>` : '',
                dateRenderer: p => p.value ? new Date(p.value).toLocaleDateString() : '—',
                actionsRenderer: p => p.data ?
                    `<div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-ghost btn-icon edit-btn" data-id="${p.data.id}" title="Edit"><ion-icon name="create-outline"></ion-icon></button>
                        <button class="btn btn-sm btn-ghost btn-icon text-danger delete-btn" data-id="${p.data.id}" title="Delete"><ion-icon name="trash-outline"></ion-icon></button>
                    </div>` : ''
            };
            gridApi = gridHelper.init();
            if (!gridApi) throw new Error("AG Grid API failed to initialize.");
        } catch (e) {
            console.error("Fatal error initializing page components:", e);
            return () => {}; // Return empty cleanup on failure
        }

        const refreshGridData = async () => {
            if (!gridApi) return;
            try {
                gridApi.showLoadingOverlay();
                const result = await App.api('users');
                gridApi.setGridOption('rowData', result.data || []);
            } catch (error) {
                App.notify.error("Could not load user data.");
                gridApi.setGridOption('rowData', []);
            }
        };

        const openModal = (mode = 'create', data = null) => {
            form.reset();
            getEl('modal-errors').classList.add('d-none');
            getEl('user-id').value = '';
            getEl('modal-title').textContent = mode === 'edit' ? `Edit User: ${data.name || ''}` : 'Create New User';
            if (mode === 'edit' && data) {
                getEl('user-id').value = data.id || '';
                getEl('user-name').value = data.name || '';
                getEl('user-email').value = data.email || '';
                getEl('user-tenant').value = data.tenant_id || '';
                getEl('user-is-admin').checked = !!data.is_tenant_admin;
            }
            userModal.show();
        };
        
        // (Rest of the handler functions: displayFormErrors, handleCreateClick, etc. remain the same)
        const displayFormErrors = (errors) => { /* ... */ };
        const handleCreateClick = () => openModal('create');
        const handleFormSubmit = async (e) => { /* ... */ };
        const handleGridClick = async (e) => { /* ... */ };

        createBtn?.addEventListener('click', handleCreateClick);
        form?.addEventListener('submit', handleFormSubmit);
        gridHelper.gridDiv?.addEventListener('click', handleGridClick);

        refreshGridData();

        // The cleanup function is critical for SPA stability.
        return () => {
            console.log("Cleaning up Users page resources.");
            gridHelper.destroy();
            if (userModal) userModal.dispose();
            createBtn?.removeEventListener('click', handleCreateClick);
            form?.removeEventListener('submit', handleFormSubmit);
            gridHelper.gridDiv?.removeEventListener('click', handleGridClick);
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        };
    };

    if (window.App && window.App.pageInitializers) {
        window.App.pageInitializers.users = initializeUsersPage;
    } else {
        document.addEventListener('DOMContentLoaded', initializeUsersPage);
    }
})();
</script>