<?php // app/views/system/tenants/edit.php

// The $tenant variable is passed from the route
$page_scripts = js('tenants/tenants-form.js');
?>
<div id="tenant-edit-page" data-tenant-id="<?= h($tenant['id']) ?>">
    <?= form_open([
        'id' => 'tenant-form',
        'action' => '/api/tenants/' . h($tenant['id']),
        'method' => 'POST',
        'autocomplete' => 'off'
    ]); ?>

    <?= card([
        'header' => ['title' => 'Edit Tenant'],
        'body' => render_partial(__DIR__ . '/_form.php', ['tenant' => $tenant]),
        'footer' => '
            <a href="/tenants" class="btn btn-secondary">Cancel</a>
            ' . form_submit('Update Tenant', ['attributes' => ['id' => 'save-tenant-btn']]) . '
        '
    ]); ?>

    <?= form_close(); ?>
</div>