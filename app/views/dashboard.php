<?php
/**
 * app/views/dashboard.php
 * Redesigned with the new component system.
 */
$user_name = h(session('user_name', 'Developer'));
?>
<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="mb-4">
        <h1 class="h2">Welcome, <?= $user_name ?>!</h1>
        <p>You've successfully logged into the PHP Functional Mini-Framework.</p>
    </div>

    <!-- About This Project Card -->
    <?php echo card([
        'header' => ['title' => 'About This Framework'],
        'body' => '
            <p>This project is an exploration of building a modern, API-first web application using a <strong>functional programming paradigm</strong> in PHP. Instead of relying on heavy classes or a traditional MVC structure, the core is built with simple, reusable helper functions.</p>
            <p class="mb-0">The goal is to provide a lean, understandable, and multi-tenant capable foundation that is both easy to learn and powerful enough for real-world projects.</p>
        '
    ]); ?>
</div>