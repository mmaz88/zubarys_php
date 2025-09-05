<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserActivitiesTable extends AbstractMigration
{
    /**
     * This table logs significant actions performed by users for auditing and tracking.
     */
    public function change(): void
    {
        $table = $this->table('user_activities', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('action', 'string', [
                'limit' => 255,
                'comment' => 'A key describing the action, e.g., "user.login", "tenant.create".'
            ])
            ->addColumn('details', 'text', [
                'null' => true,
                'comment' => 'JSON-encoded context about the activity.'
            ])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])

            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])

            // Add an index on the action column for faster querying
            ->addIndex(['action'])

            ->create();
    }
}