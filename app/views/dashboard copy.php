<?php
/**
 * app/views/dashboard.php
 * Redesigned with Bootstrap 5 components and utilities.
 */
$user_name = h(session('user_name', 'Developer'));
?>
<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="mb-4">
        <h1 class="h2">Welcome, <?= $user_name ?>!</h1>
        <p class="text-muted">You've successfully logged into the PHP Functional Mini-StarterKit.</p>
    </div>

    <!-- About This Project Card -->
    <?php
    echo card([
        'header' => [
            'title' => 'About This StarterKit',
            'subtitle' => 'A lightweight foundation for modern PHP applications.'
        ],
        'body' => '
            <p>This project is an exploration of building a modern, API-first web application using a <strong>functional programming paradigm</strong> in PHP. Instead of relying on heavy classes, complex inheritance, or a traditional MVC structure, the core is built with simple, reusable helper functions.</p>
            <p class="mb-0">The goal is to provide a lean, understandable, and multi-tenant capable foundation that is both easy to learn and powerful enough for real-world projects.</p>
        '
    ]);
    ?>

    <!-- Key Features Section -->
    <div class="mt-5">
        <h2 class="h4 mb-3">Key Features</h2>
        <?php
        $features = [
            ['icon' => 'flash-outline', 'title' => 'Functional Core', 'description' => 'A lean codebase built with simple, reusable helper functions for clarity and performance.'],
            ['icon' => 'code-working-outline', 'title' => 'API-First Design', 'description' => 'Clean separation between the backend logic and the frontend, perfect for modern web apps.'],
            ['icon' => 'layers-outline', 'title' => 'Component-Based UI', 'description' => 'Quickly build consistent interfaces with a library of view helpers for cards, forms, and buttons.'],
            ['icon' => 'server-outline', 'title' => 'Database Agnostic', 'description' => 'Powered by PDO with a simple query builder and Phinx for version-controlled migrations.'],
            ['icon' => 'business-outline', 'title' => 'Multi-Tenant Ready', 'description' => 'Designed with a shared database tenancy model for scalable, isolated client environments.'],
            ['icon' => 'shield-checkmark-outline', 'title' => 'Security Focused', 'description' => 'Includes built-in CSRF protection, password hashing, and a flexible middleware pipeline.']
        ];
        $feature_cards = [];
        foreach ($features as $feature) {
            $card_html = card([
                'body' => '
                    <div class="d-flex align-items-start">
                        <ion-icon name="' . h($feature['icon']) . '" class="text-accent fs-2 me-3 mt-1"></ion-icon>
                        <div>
                            <h5 class="card-title">' . h($feature['title']) . '</h5>
                            <p class="card-text text-muted small mb-0">' . h($feature['description']) . '</p>
                        </div>
                    </div>
                ',
                'attributes' => ['class' => 'h-100 shadow-sm']
            ]);
            $feature_cards[] = $card_html;
        }
        echo card_grid($feature_cards, ['columns' => '1 md-2 xl-3', 'gap' => '4']);
        ?>
    </div>

    <!-- Next Steps & System Info Section -->
    <div class="row g-4 mt-4">
        <div class="col-lg-6">
            <?php
            echo card([
                'header' => ['title' => 'Explore the UI Components'],
                'body' => '<p class="mb-0">The best way to get started is by exploring the component library. See how the view helpers work and test them in a live environment.</p>',
                'footer' => [
                    'actions' => button('Go to Component Library', [
                        'href' => '/components', // Assuming you'll create this route
                        'variant' => 'primary',
                        'icon' => 'arrow-forward-outline',
                        'icon_position' => 'after',
                        'attributes' => ['class' => 'w-100']
                    ])
                ],
                'attributes' => ['class' => 'h-100']
            ]);
            ?>
        </div>
        <div class="col-lg-6">
            <?php
            echo card([
                'header' => ['title' => 'System Information'],
                'body' => '
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PHP Version:
                            <strong>' . phpversion() . '</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            App Environment:
                            <span class="badge text-bg-secondary">' . h(env('APP_ENV', 'production')) . '</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Debug Mode:
                            <strong>' . (is_debug() ? '<span class="text-success">Enabled</span>' : '<span class="text-danger">Disabled</span>') . '</strong>
                        </li>
                    </ul>
                ',
                'attributes' => ['class' => 'h-100']
            ]);
            ?>
        </div>
    </div>
</div>