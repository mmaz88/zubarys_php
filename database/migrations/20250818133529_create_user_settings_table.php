<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserSettingsTable extends AbstractMigration
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
        $table = $this->table('user_settings');

        $table
            // Foreign key to the users table
            ->addColumn('user_id', 'uuid', [
                'null' => false,
                'comment' => 'The user this setting belongs to.'
            ])
            // The key for the setting, e.g., 'dark_mode', 'language'
            ->addColumn('setting_key', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'The unique key for the user setting (e.g., dark_mode_enabled).'
            ])
            // The value of the setting
            ->addColumn('setting_value', 'text', [
                'null' => true,
                'comment' => 'The value of the setting.'
            ])
            // Standard timestamps
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('updated_at', 'timestamp', [
                'null' => true,
                'update' => 'CURRENT_TIMESTAMP'
            ])
            // Add a foreign key constraint
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE', // If a user is deleted, also delete their settings
                'update' => 'CASCADE'
            ])
            // Add a unique index to prevent duplicate keys for the same user
            ->addIndex(['user_id', 'setting_key'], ['unique' => true])
            ->create();
    }
}
