<?php
// app/views/system/roles/index.php

// 1. Define the DataTables configuration. It contains all settings for the table's behavior and appearance.
$datatable_options = [
    'serverSide' => true,
    'ajax' => [
        'url' => '/api/roles',
        'type' => 'POST'
    ],
    'order' => [[0, 'asc']],
    'columns' => [
        dt_text_column('name', 'Role Name & Description', ['render' => 'roleNameRenderer']),
        dt_text_column('tenant_name', 'Scope', ['render' => 'scopeRenderer']),
        dt_number_column('user_count', 'Users Assigned'),
        dt_actions_column([
            'edit' => [
                'icon' => 'pencil-outline',
                'path' => '/roles/%ID%/edit',
                'title' => 'Edit Role'
            ],
            'delete' => [
                'icon' => 'trash-outline',
                'class' => 'text-danger',
                'isButton' => true,
                'title' => 'Delete Role'
            ]
        ])
    ],
    // Configuration for the export buttons. The JS will place these in the dropdown.
    'buttons' => [
        [
            'extend' => 'excelHtml5',
            'text' => '<ion-icon name="grid-outline" class="me-2"></ion-icon>Export to Excel',
            'className' => 'dropdown-item',
            'title' => 'Roles Export - ' . date('Y-m-d'),
            'exportOptions' => ['columns' => [0, 1, 2]]
        ],
        [
            'extend' => 'pdfHtml5',
            'text' => '<ion-icon name="document-text-outline" class="me-2"></ion-icon>Export to PDF',
            'className' => 'dropdown-item',
            'title' => 'Roles Export - ' . date('Y-m-d'),
            'exportOptions' => ['columns' => [0, 1, 2]]
        ]
    ]
];
?>

<!-- ======================= PAGE CONTENT ======================= -->
<div id="roles-page-container">
    <?php
    // 2. Build the HTML for the header actions (Export and Create buttons).
    $header_actions = '<div class="d-flex align-items-center gap-2">';

    // Export Dropdown Button
    $header_actions .= '
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="export-menu-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <ion-icon name="download-outline"></ion-icon>
                <span class="d-none d-sm-inline ms-2">Export</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="export-menu-btn" id="export-buttons-container">
                <!-- DataTables buttons will be inserted here by JavaScript -->
            </div>
        </div>';

    // Conditionally add the "Create Role" button based on user permissions.
    if (in_array('roles.create', session('permissions', [])) || session('is_app_admin')) {
        $header_actions .= button('Create Role', [
            'variant' => 'primary',
            'icon' => 'add-outline',
            'href' => '/roles/create'
        ]);
    }
    $header_actions .= '</div>';

    // 3. Render the main card. The `render_datatable` helper is now called within the body of the card.
    echo card([
        'header' => [
            'title' => 'All Roles',
            'actions' => $header_actions
        ],
        'body' => render_datatable('roles-table', $datatable_options),
        'attributes' => ['class' => 'card-body-flush'] // Removes padding around the table
    ]);
    ?>
</div>

<!-- ======================= PAGE-SPECIFIC JAVASCRIPT ======================= -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tableId = 'roles-table';
        const tableEl = document.getElementById(tableId);
        if (!tableEl) return;

        // Define custom renderers for specific columns.
        EnhancedDataTablesHelper.Renderers.roleNameRenderer = (data, type, row) => {
            const desc = row.description ? `<div class="text-muted small">${App.escapeHTML(row.description)}</div>` : '';
            return `<div class="fw-medium">${App.escapeHTML(row.name)}</div>${desc}`;
        };

        EnhancedDataTablesHelper.Renderers.scopeRenderer = (data, type, row) => {
            if (!row.tenant_id) {
                return `<span class="badge bg-info-subtle text-info-emphasis">Global</span>`;
            }
            return `<span class="badge bg-secondary-subtle text-secondary-emphasis">${App.escapeHTML(data || 'Tenant')}</span>`;
        };

        // Use the 'init.dt' event to safely interact with the table after it's fully initialized.
        $('#' + tableId).on('init.dt', function () {
            const rolesTable = $(this).DataTable();

            // Initialize DataTables Buttons and append them to our custom dropdown container.
            new $.fn.dataTable.Buttons(rolesTable, {
                buttons: rolesTable.settings()[0].oInit.buttons
            }).container().appendTo('#export-buttons-container');

            // Use event delegation to handle clicks on delete buttons within the table.
            $(tableEl).on('click', 'button[data-action="delete"]', async function () {
                const rowData = rolesTable.row($(this).closest('tr')).data();

                // Check for delete permission from the API data.
                if (!rowData.can || !rowData.can.delete) {
                    App.notify.error("You don't have permission to delete this role.");
                    return;
                }

                try {
                    // Show a confirmation modal before proceeding.
                    await App.confirm({
                        title: 'Delete Role?',
                        message: `Are you sure you want to delete the "<strong>${rowData.name}</strong>" role?`,
                        confirmVariant: 'danger'
                    });

                    // If confirmed, send the delete request.
                    const result = await App.api(`roles/${rowData.id}/delete`, { method: 'POST' });
                    App.notify.success(result.message);
                    rolesTable.ajax.reload(); // Refresh the table data
                } catch (error) {
                    // This block catches API errors or if the user cancels the confirmation.
                    if (error && error.message) {
                        App.notify.error(error.message);
                    }
                }
            });
        });
    });
</script>