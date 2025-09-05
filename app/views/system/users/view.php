<?php
/**
 * app/views/system/users/view.php
 * Displays a detailed profile for a single user.
 */
?>

<div id="user-view-page-container" data-page-id="user-view">
    <!-- Action buttons (like Edit) will be rendered here by JavaScript -->
    <div class="d-flex justify-content-end align-items-center mb-4" id="view-page-actions"></div>

    <div id="user-profile-container">
        <!-- Loading placeholder shown while data is being fetched -->
        <div id="loading-placeholder">
            <?= card([
                'body' => '<div class="d-flex align-items-center justify-content-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
            ]) ?>
        </div>

        <!-- The actual user profile content will be rendered here by JavaScript -->
        <div id="user-profile-content" class="hidden"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const userId = '<?= h($user_id ?? '') ?>';
        const container = document.getElementById('user-profile-content');
        const loader = document.getElementById('loading-placeholder');
        const actionsContainer = document.getElementById('view-page-actions');

        if (!userId) {
            loader.innerHTML = `<?= card(['body' => '<div class="alert alert-danger m-0">Error: User ID not provided.</div>']) ?>`;
            return;
        }

        /**
         * Renders the action buttons (e.g., Edit) based on permissions.
         */
        const renderActions = (user) => {
            let actionsHtml = '';
            if (user.can && user.can.edit) {
                actionsHtml += `
                <a href="/users/${App.escapeHTML(user.id)}/edit" class="btn btn-secondary">
                    <ion-icon name="pencil-outline" class="me-2"></ion-icon>
                    <span>Edit User</span>
                </a>`;
            }
            actionsContainer.innerHTML = actionsHtml;
        };

        /**
         * Renders the entire user profile using the data from the API.
         */
        const renderProfile = (user) => {
            renderActions(user);

            const adminBadges = [];
            if (user.is_app_admin) {
                adminBadges.push('<span class="badge bg-info-subtle text-info-emphasis">Super Admin</span>');
            }
            if (user.is_tenant_admin) {
                adminBadges.push('<span class="badge bg-success-subtle text-success-emphasis">Tenant Admin</span>');
            }

            const profileHeader = `
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-4 mb-4">
                <div class="avatar-placeholder">${App.escapeHTML(user.name.charAt(0))}</div>
                <div class="flex-grow-1">
                    <h1 class="h3 mb-0">${App.escapeHTML(user.name)}</h1>
                    <p class="text-muted mb-2">${App.escapeHTML(user.email)}</p>
                    <div class="d-flex align-items-center gap-2">${adminBadges.join('')}</div>
                </div>
            </div>`;

            // --- THE FIX IS HERE ---
            // Manually build the HTML for the cards instead of calling a PHP function.
            const detailsCardHtml = `
            <div class="card">
                <div class="card-header"><h3 class="card-title">User Details</h3></div>
                <div class="card-body">
                    <dl class="details-list">
                        <div class="details-list-item"><dt>Tenant</dt><dd>${user.tenant_name ? App.escapeHTML(user.tenant_name) : '<span class="text-muted">N/A</span>'}</dd></div>
                        <div class="details-list-item"><dt>User ID</dt><dd><code>${user.id}</code></dd></div>
                        <div class="details-list-item"><dt>Joined On</dt><dd>${new Date(user.created_at).toLocaleDateString()}</dd></div>
                    </dl>
                </div>
            </div>`;

            const rolesCardHtml = `
            <div class="card">
                <div class="card-header"><h3 class="card-title">Assigned Roles</h3></div>
                <div class="card-body">
                    ${user.roles && user.roles.length > 0
                    ? `<div class="d-flex flex-wrap gap-2">${user.roles.map(role => `<span class="badge rounded-pill bg-secondary">${App.escapeHTML(role)}</span>`).join('')}</div>`
                    : '<p class="text-muted mb-0">This user has no roles assigned.</p>'
                }
                </div>
            </div>`;
            // --- END OF FIX ---


            container.innerHTML = `
            ${profileHeader}
            <div class="row g-4">
                <div class="col-lg-6">${detailsCardHtml}</div>
                <div class="col-lg-6">${rolesCardHtml}</div>
            </div>`;
        };

        /**
         * Fetches the user data from the API.
         */
        const fetchUser = async () => {
            try {
                const result = await App.api(`users/${userId}`);
                renderProfile(result.data);
                loader.classList.add('hidden');
                container.classList.remove('hidden');
            } catch (error) {
                const errorMsg = App.escapeHTML(error.message || 'The user could not be loaded.');
                const errorCardHtml = `
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger m-0 text-center">
                            <strong>${error.status || 'Error'} | Could not load user</strong>
                            <p class="mb-0 mt-1">${errorMsg}</p>
                        </div>
                    </div>
                </div>`;
                loader.innerHTML = errorCardHtml;
            }
        };

        fetchUser();
    });
</script>

<!-- View-specific styles for the details list and avatar -->
<style>
    .avatar-placeholder {
        width: 80px;
        height: 80px;
        flex-shrink: 0;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background-color: hsl(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        font-size: 2.5rem;
        font-weight: 600;
        border: 2px solid var(--bs-primary);
    }

    .details-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .details-list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
        border-bottom: 1px solid var(--bs-border-color-translucent);
        padding-bottom: 0.75rem;
    }

    .details-list-item:last-child {
        border-bottom: none;
    }

    .details-list-item dt {
        color: var(--bs-secondary-color);
    }

    .details-list-item dd {
        font-weight: 500;
        color: var(--bs-body-color);
        text-align: right;
        margin-bottom: 0;
    }

    .hidden {
        display: none !important;
    }
</style>