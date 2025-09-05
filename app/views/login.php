<?php // app/views/login.php ?>
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-md mx-auto">
        <?= card([
            'body' => '
                <form id="login-form" autocomplete="off">
                    ' . csrf_field() . '
                    <div class="text-center mb-6">
                        <ion-icon name="cube-outline" class="text-primary text-5xl"></ion-icon>
                        <h1 class="text-2xl font-bold mt-2">Sign in to your account</h1>
                        <p class="text-sm text-muted-foreground mt-1">Log in as a Super Admin or a Tenant Admin.</p>
                    </div>

                    <div id="error-message-container" class="mb-4"></div>

                    ' . form_input('email', [
                    'label' => 'Email address',
                    'required' => true,
                    'attributes' => [
                        'placeholder' => 'Enter your email',
                        'value' => 'superadmin@dev.com' // Default to Super Admin for convenience
                    ]
                ]) . '

                    ' . form_input('password', [
                    'type' => 'password',
                    'label' => 'Password',
                    'required' => true,
                    'attributes' => [
                        'placeholder' => '•••••••••',
                        'value' => '123456789'
                    ]
                ]) . '

                    <div class="flex items-center justify-between my-4">
                        ' . form_checkbox('remember_me', ['label' => 'Remember me']) . '
                        <a href="#" class="text-sm text-primary hover:underline">Forgot password?</a>
                    </div>

                    ' . button('Sign in', [
                    'variant' => 'primary',
                    'type' => 'submit',
                    'attributes' => ['class' => 'w-full']
                ]) . '

                </form>
            ',
            'footer' => '
                <div class="text-sm text-left w-full p-3 bg-muted/50 border rounded-lg">
                    <h4 class="font-semibold mb-2">Default Accounts</h4>
                    <ul class="list-none space-y-2">
                        <li>
                            <strong>Super Admin:</strong> Manages the entire application, including tenants and global settings.
                            <br>
                            <code class="text-xs">superadmin@dev.com</code>
                        </li>
                        <li>
                            <strong>Tenant Admin:</strong> Manages users and resources for a specific tenant ("Acme Corporation").
                            <br>
                            <code class="text-xs">admin@acme.com</code>
                        </li>
                    </ul>
                    <p class="text-xs mt-2">Password for both is: <code class="text-xs">123456789</code></p>
                </div>
            '
        ]) ?>
    </div>
</div>