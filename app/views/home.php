<?php // app/views/home.php - A better home page ?>
<div class="relative bg-background overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative z-10 py-16 sm:py-24 lg:py-32">
            <div class="max-w-2xl mx-auto text-center">
                <h1 class="text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                    A Modern Foundation for PHP Applications
                </h1>
                <p class="mt-6 text-xl text-muted-foreground">
                    Built with a functional, API-first approach. Lightweight, multi-tenant capable, and designed for performance and clarity.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <?= button('Get Started', ['href' => '/register', 'variant' => 'primary', 'size' => 'lg']) ?>
                    <?= button('View Source', ['href' => 'https://github.com/zubary/php-functional-mini-framework', 'variant' => 'secondary', 'size' => 'lg', 'attributes' => ['target' => '_blank']]) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-subtle py-24 sm:py-32">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto lg:text-center">
            <h2 class="text-base font-semibold leading-7 text-primary">Core Features</h2>
            <p class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                Everything you need for a robust backend
            </p>
            <p class="mt-6 text-lg leading-8 text-muted-foreground">
                This framework provides a solid, secure, and scalable starting point for your next project, letting you focus on features, not boilerplate.
            </p>
        </div>
        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
            <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                <?php
                $features = [
                    ['icon' => 'layers-outline', 'title' => 'Functional Core', 'description' => 'Built with simple, reusable helper functions instead of complex classes for a lean and understandable codebase.'],
                    ['icon' => 'business-outline', 'title' => 'Multi-Tenant Ready', 'description' => 'Designed with a shared database tenancy model, allowing for scalable, isolated client environments.'],
                    ['icon' => 'flash-outline', 'title' => 'API-First Design', 'description' => 'A clear separation between the frontend and backend with well-defined API routes, perfect for modern applications.'],
                    ['icon' => 'server-outline', 'title' => 'Database Migrations', 'description' => 'Powered by Phinx for robust, version-controlled schema management, ensuring database consistency.'],
                    ['icon' => 'shield-checkmark-outline', 'title' => 'Security Focused', 'description' => 'Includes built-in CSRF protection, password hashing, and a middleware pipeline for authentication.'],
                    ['icon' => 'grid-outline', 'title' => 'Component-Based UI', 'description' => 'Reusable UI components and helpers for a consistent and modern user interface that is easy to extend.']
                ];
                foreach ($features as $feature):
                    ?>
                        <div class="flex flex-col">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-foreground">
                                <ion-icon name="<?= h($feature['icon']) ?>" class="h-5 w-5 flex-none text-primary" aria-hidden="true"></ion-icon>
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