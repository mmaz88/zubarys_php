<?php // app/views/layout/main.php
include 'partials/_menu.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title ?? 'PHP Func') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?= js('core/ThemeManager.js') ?>
    <?= js('core/SidebarManager.js') ?>
    <?= css('main.css') ?>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <!-- AG Grid assets -->
    <?php // echo css('ag-theme-quartz.css') ?>
    <!-- <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@34.1.2/dist/ag-grid-community.min.js"></script> -->
    <!-- SPA Loading Overlay Style -->
    <style>
        #spa-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: hsl(var(--background) / 0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.3s;
        }

        #spa-loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        #spa-loading-overlay .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div id="spa-loading-overlay" class="hidden">
        <div><ion-icon name="reload-outline" style="font-size: 3rem; color: hsl(var(--primary));"
                class="animate-spin"></ion-icon></div>
    </div>
    <div class="d-flex app-container">
        <?= render_app_sidebar($menu, $brand_config) ?>

        <div class="main-content-wrapper flex-grow-1">
            <header class="app-header">
                <div class="header-left">
                    <h2 class="h4 mb-0"><?= h($page_title ?? 'Page') ?></h2>
                </div>
                <div class="header-right d-flex align-items-center gap-3">
                    <?php include APP_PATH . '/views/partials/_theme_switcher.php'; ?>
                    <?php include APP_PATH . '/views/partials/_user_profile.php' ?>
                </div>
            </header>

            <!-- DataTables Advanced Filter Popup (controlled by dataTableHelper.js) -->
            <!-- <div id="dt-filter-popup" style="display: none;">
                <div class="filter-group">
                    <select id="dt-filter-condition" class="form-select form-select-sm">
                        <option value="contains">Contains</option>
                        <option value="not_contains">Doesn't Contain</option>
                        <option value="equals">Equals</option>
                        <option value="not_equals">Doesn't Equal</option>
                        <option value="starts_with">Starts With</option>
                        <option value="ends_with">Ends With</option>
                    </select>
                    <input type="text" id="dt-filter-value" class="form-input form-input-sm"
                        placeholder="Filter value...">
                </div>
                <div class="filter-popup-actions">
                    <button id="dt-filter-clear" class="btn btn-sm btn-ghost">Clear</button>
                    <button id="dt-filter-apply" class="btn btn-sm btn-primary">Apply</button>
                </div>
            </div> -->

            <main class="app-content">
                <?= $content ?? '' /* Page content from the view will be injected here */ ?>
            </main>
        </div>
    </div>

    <?php /* Modal Placeholder Area */ ?>
    <?php include 'partials/_confirmation_modal.php'; ?>
    <?php include 'partials/_datatable_popup.php'; ?>

    <!-- Core Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <?= js('bootstrap.bundle.min.js') ?>

    <!-- DataTables & Extensions JS -->
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.bootstrap5.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>

    <!-- Custom App Logic -->
    <?= js('alert.js') ?>
    <?= js('core/app.js') ?>
    <?= js('dataTableHelper.js') ?>

    <!-- Page-specific scripts passed from the view are rendered last -->
    <?= $page_scripts ?? '' ?>
</body>

</html>