<?php /**
  * app/views/errors/404.php
  * A simple 404 page content file.
  */
?>
<div class="text-center py-12">
    <h1 class="text-6xl font-bold text-destructive">404</h1>
    <p class="text-2xl font-semibold mt-4">Resource Not Found</p>
    <p class="text-fg-muted mt-2">Sorry, the resource you are looking for does not exist or could not be found.</p>
    <div class="mt-6">
        <?= button('Go to Dashboard', ['href' => '/dashboard', 'variant' => 'primary']) ?>
    </div>
</div>