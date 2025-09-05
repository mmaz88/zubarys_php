<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTenantsTable extends AbstractMigration
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
        $table = $this->table('tenants', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('domain', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'active']) // e.g., active, suspended, disabled

            // Audit columns
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true]) // Nullable if system-created
            ->addColumn('updated_by', 'uuid', ['null' => true])

            ->addIndex(['domain'], ['unique' => true])
            ->create();
    }
}
