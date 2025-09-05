<script>
    // This script runs after the main DataTables helper script.
    // It enhances the specific 'users-table' instance.
    document.addEventListener('DOMContentLoaded', () => {
        const tableId = 'users-table';
        const tableEl = document.getElementById(tableId);

        if (!tableEl) {
            console.error('Users table element not found.');
            return;
        }

        // Use the 'init.dt' event to safely manipulate the table after it's fully initialized.
        // This is more reliable than setTimeout.
        $('#' + tableId).on('init.dt', function () {
            const dataTable = $(this).DataTable();

            // DataTables Buttons extension creates its own container.
            // We find that container and move its contents into our custom dropdown menu.
            // This requires the Buttons extension JS file to be loaded.
            new $.fn.dataTable.Buttons(dataTable, {
                buttons: dataTable.settings()[0].oInit.buttons
            }).container().appendTo('#export-buttons-container');
        });

        // Use event delegation for handling delete button clicks efficiently.
        const handleTableClick = async (e) => {
            // Target the delete button specifically using the data-action attribute.
            const deleteBtn = e.target.closest('button[data-action="delete"]');
            if (!deleteBtn) return;

            const userId = deleteBtn.dataset.id;
            if (!userId) return;

            try {
                // Use the global App.confirm modal for a better user experience.
                await App.confirm({
                    title: 'Delete User?',
                    message: 'This will permanently remove the user. This action cannot be undone.',
                    confirmVariant: 'destructive'
                });

                // If confirmed, proceed with the API call.
                const result = await App.api(`users/${userId}/delete`, { method: 'POST' });
                App.notify.success(result.message || 'User deleted successfully.');

                // Redraw the table to show the change without a full page reload.
                $('#' + tableId).DataTable().draw();
            } catch (error) {
                // App.confirm() rejects if the user cancels, so we only show an error
                // if an actual error object was thrown (e.g., from the API call).
                if (error && error.message) {
                    App.notify.error(error.message);
                }
            }
        };

        tableEl.addEventListener('click', handleTableClick);

        // In a non-SPA context, we don't need to manually clean up these listeners.
        // They will be removed when the user navigates to a new page.
    });
</script>