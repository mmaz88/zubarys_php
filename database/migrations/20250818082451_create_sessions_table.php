<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessionsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // 1. Tell Phinx not to create a default 'id' column.
        // 2. Specify that our custom 'id' column will be the primary key.
        $table = $this->table('sessions', ['id' => false, 'primary_key' => ['id']]);

        $table
            // Your custom string 'id' column for the PHP session ID
            ->addColumn('id', 'string', ['limit' => 128, 'null' => false])
            ->addColumn('user_id', 'uuid', ['null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('payload', 'text', ['null' => false])
            ->addColumn('last_activity', 'integer', ['null' => false])
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();

        // The changePrimaryKey() call is no longer needed as it's defined at creation.
    }
}