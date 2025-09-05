<?php // app/views/home.php - Rewritten Home Page ?>
<div class="relative bg-background overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative z-10 py-16 sm:py-24 lg:py-32">
            <div class="max-w-2xl mx-auto text-center">
                <h1 class="text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                    PHP Starter Kit
                </h1>
                <p class="mt-6 text-xl text-muted-foreground">
                    A modern foundation for your next PHP application. Built with a functional, API-first approach, this
                    kit is lightweight, multi-tenant capable, and designed for performance and clarity.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <?= button('Get Started', ['href' => '/register', 'variant' => 'primary', 'size' => 'lg']) ?>
                    <?= button('View Source on GitHub', ['href' => 'https://github.com/mmaz88/zubarys_php', 'variant' => 'secondary', 'size' => 'lg', 'attributes' => ['target' => '_blank']]) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Core Backend Features Section -->
<div class="bg-card py-24 sm:py-32">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto lg:text-center">
            <h2 class="text-base font-semibold leading-7 text-primary">Backend Architecture</h2>
            <p class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                Everything you need for a robust backend
            </p>
            <p class="mt-6 text-lg leading-8 text-muted-foreground">
                This starter kit provides a solid, secure, and scalable starting point, letting you focus on building
                features, not boilerplate.
            </p>
        </div>
        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
            <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                <?php $features = [
                    ['icon' => 'flash-outline', 'title' => 'Functional Core', 'description' => 'Built with simple, reusable helper functions instead of complex classes for a lean, understandable, and performant codebase.'],
                    ['icon' => 'code-working-outline', 'title' => 'API-First Design', 'description' => 'A clear separation between the frontend and backend with well-defined API routes, perfect for modern, scalable applications.'],
                    ['icon' => 'business-outline', 'title' => 'Multi-Tenant Ready', 'description' => 'Designed with a shared database tenancy model, allowing for scalable, isolated client environments right out of the box.'],
                    ['icon' => 'shield-checkmark-outline', 'title' => 'Advanced Security', 'description' => 'Includes a flexible middleware pipeline, Role-Based Access Control (RBAC), CSRF protection, and modern encryption helpers.'],
                    ['icon' => 'server-outline', 'title' => 'Powerful Data Layer', 'description' => 'Features a database-agnostic fluent query builder and Phinx for robust, version-controlled schema migrations.'],
                    ['icon' => 'mail-outline', 'title' => 'Service Integrations', 'description' => 'Ready-to-use services for sending emails (PHPMailer), SMS, and WhatsApp messages, configurable via .env.']
                ];
                foreach ($features as $feature): ?>
                    <div class="flex flex-col">
                        <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-foreground">
                            <ion-icon name="<?= h($feature['icon']) ?>" class="h-5 w-5 flex-none text-primary"
                                aria-hidden="true"></ion-icon>
                            <?= h($feature['title']) ?>
                        </dt>
                        <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                            <p class="flex-auto"><?= h($feature['description']) ?></p>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
</div>

<!-- UI Features Section -->
<div class="bg-subtle py-24 sm:py-32">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto lg:text-center">
            <h2 class="text-base font-semibold leading-7 text-primary">User Interface</h2>
            <p class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                Rapid UI Development Kit
            </p>
            <p class="mt-6 text-lg leading-8 text-muted-foreground">
                Build beautiful, consistent, and data-driven interfaces quickly with a comprehensive set of themeable UI
                components and helpers.
            </p>
        </div>
        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
            <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                <?php $ui_features = [
                    ['icon' => 'grid-outline', 'title' => 'DataTables Integration', 'description' => 'Advanced, server-side powered tables with helpers for custom renderers (status badges, user profiles), sorting, filtering, and exporting to Excel/PDF.'],
                    ['icon' => 'browsers-outline', 'title' => 'Component Helpers', 'description' => 'PHP functions to easily render Cards, Modals, Buttons, Toasts, and Alerts, ensuring a consistent design language across your application.'],
                    ['icon' => 'toggle-outline', 'title' => 'Secure Form Builder', 'description' => 'A full suite of helpers for creating secure forms with inputs, textareas, selects, and toggle switches, including automatic CSRF protection.'],
                    ['icon' => 'color-palette-outline', 'title' => 'Themeable Design', 'description' => 'A modern, Tailwind-inspired design system built with SASS and CSS variables. Includes a dark mode and multiple color themes out of the box.'],
                    ['icon' => 'menu-outline', 'title' => 'Dynamic Layouts', 'description' => 'A clean application shell with a responsive, collapsible sidebar, dynamic page titles, and a dedicated public-facing layout.'],
                    ['icon' => 'analytics-outline', 'title' => 'Interactive Charts & Stats', 'description' => 'Helpers and views for displaying system statistics and a foundation for adding rich data visualizations to your dashboard.']
                ];
                foreach ($ui_features as $feature): ?>
                    <div class="flex flex-col">
                        <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-foreground">
                            <ion-icon name="<?= h($feature['icon']) ?>" class="h-5 w-5 flex-none text-primary"
                                aria-hidden="true"></ion-icon>
                            <?= h($feature['title']) ?>
                        </dt>
                        <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                            <p class="flex-auto"><?= h($feature['description']) ?></p>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
</div>

<!-- Suggested New Features Section -->
<div class="bg-card py-24 sm:py-32">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto lg:text-center">
            <h2 class="text-base font-semibold leading-7 text-primary">Next Steps & Possibilities</h2>
            <p class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                Expand Your Application
            </p>
            <p class="mt-6 text-lg leading-8 text-muted-foreground">
                This starter kit is a foundation. Here are some powerful features you could build next to enhance your
                project.
            </p>
        </div>
        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
            <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                <?php $next_features = [
                    ['icon' => 'folder-open-outline', 'title' => 'File Manager', 'description' => 'Implement a file upload and management system for user avatars, document storage, and other media assets.'],
                    ['icon' => 'layers-outline', 'title' => 'Dashboard Widgets', 'description' => 'Create a dynamic, customizable dashboard with widgets that display key metrics, recent activities, and other important data.'],
                    ['icon' => 'cog-outline', 'title' => 'Job Queue System', 'description' => 'Integrate a background job queue for handling long-running tasks like sending bulk emails, generating reports, or processing data without blocking the UI.'],
                    ['icon' => 'notifications-outline', 'title' => 'Real-time Notifications', 'description' => 'Add a WebSocket-based system to provide users with instant notifications for important events directly in the UI.'],
                    ['icon' => 'language-outline', 'title' => 'Internationalization (i18n)', 'description' => 'Adapt your application for a global audience by adding support for multiple languages and localizations.'],
                    ['icon' => 'logo-webpack', 'title' => 'Modern Asset Bundling', 'description' => 'Integrate a tool like Vite or Webpack to bundle JavaScript and CSS assets, enabling features like Hot Module Replacement (HMR) for faster development.']
                ];
                foreach ($next_features as $feature): ?>
                    <div class="flex flex-col">
                        <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-foreground">
                            <ion-icon name="<?= h($feature['icon']) ?>" class="h-5 w-5 flex-none text-primary"
                                aria-hidden="true"></ion-icon>
                            <?= h($feature['title']) ?>
                        </dt>
                        <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                            <p class="flex-auto"><?= h($feature['description']) ?></p>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
</div>