<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRolesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('roles');
        $table
            // MODIFIED: tenant_id is now nullable to support global, app-level roles.
            ->addColumn('tenant_id', 'uuid', ['null' => true])
            ->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            // Audit columns
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            // MODIFIED: The unique index now needs to handle NULLs gracefully.
            // A unique index on (tenant_id, name) allows multiple roles named 'Reader'
            // as long as they belong to different tenants, and multiple global roles
            // are prevented by their unique name when tenant_id is NULL (database-dependent behavior).
            ->addIndex(['tenant_id', 'name'], ['unique' => true])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}