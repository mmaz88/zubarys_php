<?php // app/views/system/tenants/_form.php
$is_edit = isset($tenant);
?>

<div id="form-errors" class="alert alert-danger d-none"></div>

<div class="row g-3">
    <div class="col-md-4">
        <?= form_input('name', [
            'label' => 'Tenant Name',
            'required' => true,
            'value' => $tenant['name'] ?? ''
        ]); ?>
    </div>
    <div class="col-md-4">
        <?= form_input('domain', [
            'label' => 'Domain',
            'help_text' => 'Optional. e.g., acme.example.com',
            'value' => $tenant['domain'] ?? ''
        ]); ?>
    </div>
    <div class="col-md-2">
        <?= form_select('status', [
            'label' => 'Status',
            'required' => true,
            'options' => ['active' => 'Active', 'suspended' => 'Suspended', 'disabled' => 'Disabled'],
            'selected' => $tenant['status'] ?? 'active'
        ]); ?>
    </div>
</div>