<!-- app/views/system/tenants/_index_script.php -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tableId = 'tenants-table';
        const tableEl = document.getElementById(tableId);
        if (!tableEl) return;

        $('#' + tableId).on('init.dt', function () {
            const tenantsTable = $(this).DataTable();

            $(tableEl).on('click', 'button[data-action="delete"]', async function () {
                const rowData = tenantsTable.row($(this).closest('tr')).data();

                if (!rowData.can || !rowData.can.delete) {
                    App.notify.error("You don't have permission to delete this tenant.");
                    return;
                }

                try {
                    await App.confirm({
                        title: 'Delete Tenant?',
                        message: `Are you sure you want to delete "<strong>${rowData.name}</strong>"? This action cannot be undone.`,
                        confirmVariant: 'danger'
                    });

                    const result = await App.api(`tenants/${rowData.id}/delete`, {
                        method: 'POST'
                    });
                    App.notify.success(result.message);
                    tenantsTable.ajax.reload();
                } catch (error) {
                    if (error && error.message) {
                        App.notify.error(error.message);
                    }
                }
            });
        });
    });
</script>