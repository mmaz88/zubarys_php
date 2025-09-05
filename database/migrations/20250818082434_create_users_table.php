<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
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
        $table = $this->table('users', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('tenant_id', 'uuid', ['null' => true]) // Null only for App Admins
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('email', 'string', ['limit' => 100])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('is_app_admin', 'boolean', ['default' => false])
            ->addColumn('is_tenant_admin', 'boolean', ['default' => false])

            // Audit columns
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('updated_by', 'uuid', ['null' => true])

            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['tenant_id'])

            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
