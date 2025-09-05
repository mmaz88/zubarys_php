<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTenantSettingsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Get the table object
        $table = $this->table('tenant_settings');

        $table
            // Foreign key to the tenants table
            ->addColumn('tenant_id', 'uuid', [
                'null' => false,
                'comment' => 'The tenant this setting belongs to.'
            ])
            // The key for the setting, e.g., 'theme_color', 'logo_url'
            ->addColumn('setting_key', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'The unique key for the setting (e.g., theme_color).'
            ])
            // The value of the setting, stored as text to be flexible (can hold strings, JSON, etc.)
            ->addColumn('setting_value', 'text', [
                'null' => true,
                'comment' => 'The value of the setting (can be a string, JSON, etc.).'
            ])
            // Standard timestamps
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('updated_at', 'timestamp', [
                'null' => true,
                'update' => 'CURRENT_TIMESTAMP' // Automatically update on row change
            ])
            // Add a foreign key constraint to ensure data integrity
            ->addForeignKey('tenant_id', 'tenants', 'id', [
                'delete' => 'CASCADE', // If a tenant is deleted, also delete their settings
                'update' => 'CASCADE'
            ])
            // Add a unique index to prevent duplicate keys for the same tenant
            ->addIndex(['tenant_id', 'setting_key'], ['unique' => true])
            ->create();
    }
}
