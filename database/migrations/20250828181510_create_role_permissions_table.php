<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRolePermissionsTable extends AbstractMigration
{
    /**
     * This table links roles to the permissions they grant.
     */
    public function change(): void
    {
        $table = $this->table('role_permissions', ['id' => false, 'primary_key' => ['role_id', 'permission_id']]);
        $table
            ->addColumn('role_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('permission_id', 'integer', ['null' => false, 'signed' => false])
            // Timestamps can be useful for auditing when a permission was granted to a role
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])

            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])

            ->create();
    }
}