<?php // app/views/layout/public.php ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title ?? 'PHP Func') ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- 1. Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- 2. Your Compiled Custom Stylesheet -->
    <?= css('main.css') ?>

    <!-- Theme Switcher (already correct) -->
    <script>
        // Set theme from local storage on initial load to prevent FOUC
        (function () {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <!-- Icons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="flex flex-col min-h-screen">
        <header class="bg-card border-b border-border sticky top-0 z-50">
            <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <a href="/" class="flex-shrink-0 flex items-center gap-2 font-bold text-lg text-foreground">
                            <ion-icon name="cube-outline" class="text-primary text-2xl"></ion-icon>
                            <span><?= h(config('app.name', 'PHP Func')) ?></span>
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="/login" class="btn btn-ghost">Sign In</a>
                        <a href="/register" class="btn btn-primary">Get Started</a>
                    </div>
                </div>
            </nav>
        </header>

        <main class="flex-grow">
            <?= $content ?? '' ?>
        </main>

        <footer class="bg-card border-t border-border py-6">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center text-muted-foreground text-sm">
                <p>&copy; <?= date('Y') ?> <?= h(config('app.name')) ?>. All rights reserved.</p>
                <p class="mt-1">PHP v<?= phpversion() ?> | Built for Scale</p>
            </div>
        </footer>
    </div>

    <!-- Essential Scripts -->
    <?= js('alert.js') ?>
    <?= js('auth/login.js') ?>
</body>

</html>