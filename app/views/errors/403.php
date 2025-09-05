<?php // app/views/errors/403.php ?>
<div class="text-center py-12">
    <h1 class="text-6xl font-bold text-destructive">403</h1>
    <p class="text-2xl font-semibold mt-4">Forbidden</p>
    <p class="text-fg-muted mt-2">Sorry, you do not have permission to access this resource.</p>
    <div class="mt-6">
        <?= button('Go to Dashboard', ['href' => '/dashboard', 'variant' => 'primary']) ?>
    </div>
</div>