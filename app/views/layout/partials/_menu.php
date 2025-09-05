<?php
// app/views/layout/main.php
// The data for the sidebar remains dynamic
$menu = [
    ['text' => 'Main Navigation', 'is_heading' => true],
    ['text' => 'Dashboard', 'slug' => 'dashboard', 'icon' => 'grid-outline'],
    ['text' => 'Core Systems', 'is_heading' => true],
    ['text' => 'Tenants', 'slug' => 'tenants', 'icon' => 'business-outline'],
    ['text' => 'Users', 'slug' => 'users', 'icon' => 'people-outline'],
    ['text' => 'Roles & Permissions', 'slug' => 'roles', 'icon' => 'shield-checkmark-outline'],
];
$brand_config = ['name' => 'PHP Func', 'url' => '/dashboard'];

// User-specific data
$user_name = h(session('user_name', 'Acme Admin'));
$user_avatar = 'https://i.pravatar.cc/150?u=' . h(session('user_id'));
?>