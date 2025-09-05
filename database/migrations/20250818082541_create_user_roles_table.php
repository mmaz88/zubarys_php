<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserRolesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user_roles', ['id' => false, 'primary_key' => ['user_id', 'role_id']]);
        $table
            ->addColumn('user_id', 'uuid', ['null' => false])
            // FIX: Make the integer UNSIGNED to match the 'roles.id' column
            ->addColumn('role_id', 'integer', ['null' => false, 'signed' => false])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}