<?php // app/views/register.php ?>
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-md mx-auto">
        <?= card([
            'header' => ['title' => 'Create an Account'],
            'body' => '<p class="text-center text-muted-foreground p-4">Registration is currently disabled.</p>',
            'footer' => '<div class="text-center w-full"><a href="/login" class="text-primary hover:underline">Back to Sign In</a></div>'
        ]) ?>
    </div>
</div>