<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserPermissionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user_permissions', ['id' => false, 'primary_key' => ['user_id', 'permission_id']]);
        $table
            ->addColumn('user_id', 'uuid', ['null' => false])
            // FIX: Make the integer UNSIGNED to match the 'permissions.id' column
            ->addColumn('permission_id', 'integer', ['null' => false, 'signed' => false])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}