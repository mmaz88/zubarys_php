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
                    </div>
                    <div id="error-message-container" class="mb-4"></div>
                    ' . form_input('email', [
                    'label' => 'Email address',
                    'required' => true,
                    'attributes' => ['placeholder' => 'admin@acme.com', 'value' => 'superadmin@dev.com']
                ]) . '
                    ' . form_input('password', [
                    'type' => 'password',
                    'label' => 'Password',
                    'required' => true,
                    'attributes' => ['placeholder' => '••••••••', 'value' => '123456789']
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
                    <p class="text-center text-sm text-muted-foreground mt-6">
                        Don\'t have an account?
                        <a href="/register" class="text-primary hover:underline font-medium">Sign up</a>
                    </p>
                </form>
            '
        ]) ?>
    </div>
</div>