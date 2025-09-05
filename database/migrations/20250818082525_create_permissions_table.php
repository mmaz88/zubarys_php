<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePermissionsTable extends AbstractMigration
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
        $table = $this->table('permissions');
        $table
            ->addColumn('slug', 'string', ['limit' => 50]) // Programmatic key, e.g., 'users.create'
            ->addColumn('description', 'string', ['limit' => 255])

            // Audit columns
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])

            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }
}
