<?php // app/views/system/tenants/create.php

$page_scripts = js('tenants/tenants-form.js');
?>
<div id="tenant-create-page">
    <?= form_open([
        'id' => 'tenant-form',
        'action' => '/api/tenants',
        'method' => 'POST',
        'autocomplete' => 'off'
    ]); ?>

    <?= card([
        'header' => ['title' => 'Create New Tenant'],
        'body' => render_partial(__DIR__ . '/_form.php'),
        'footer' => '
            <a href="/tenants" class="btn btn-secondary">Cancel</a>
            ' . form_submit('Create Tenant', ['attributes' => ['id' => 'save-tenant-btn']]) . '
        '
    ]); ?>

    <?= form_close(); ?>
</div>