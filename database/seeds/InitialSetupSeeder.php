<?php
use Phinx\Seed\AbstractSeed;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\SqliteAdapter;
use Phinx\Db\Adapter\PostgresAdapter; // <-- CORRECTED ADAPTER NAME

class InitialSetupSeeder extends AbstractSeed
{
    /**
     * A private helper method to disable foreign key checks in a database-agnostic way.
     */
    private function disableForeignKeyChecks(): void
    {
        $adapter = $this->getAdapter();
        if ($adapter instanceof MysqlAdapter) {
            $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        } elseif ($adapter instanceof SqliteAdapter) {
            $this->execute('PRAGMA foreign_keys = OFF');
        } elseif ($adapter instanceof PostgresAdapter) {
            $this->execute("SET session_replication_role = 'replica'");
        }
    }

    /**
     * A private helper method to re-enable foreign key checks.
     */
    private function enableForeignKeyChecks(): void
    {
        $adapter = $this->getAdapter();
        if ($adapter instanceof MysqlAdapter) {
            $this->execute('SET FOREIGN_KEY_CHECKS = 1');
        } elseif ($adapter instanceof SqliteAdapter) {
            $this->execute('PRAGMA foreign_keys = ON');
        } elseif ($adapter instanceof PostgresAdapter) {
            $this->execute("SET session_replication_role = 'origin'");
        }
    }

    /**
     * A simple UUIDv4 generator.
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    public function run(): void
    {
        // --- 0. Clean Existing Data ---
        $this->disableForeignKeyChecks();
        $this->table('role_permissions')->truncate();
        $this->table('user_roles')->truncate();
        $this->table('permissions')->truncate();
        $this->table('roles')->truncate();
        $this->table('users')->truncate();
        $this->table('tenants')->truncate();
        $this->enableForeignKeyChecks();

        // --- 1. Create a default Tenant ---
        $tenantId = $this->generateUuid();
        $this->table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Acme Corporation',
            'domain' => 'acme.example.com',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ])->saveData();
        echo "Created Tenant: Acme Corporation\n";

        // --- 2. Create the Tenant's Admin User ---
        $tenantAdminId = $this->generateUuid();
        $this->table('users')->insert([
            'id' => $tenantAdminId,
            'tenant_id' => $tenantId,
            'name' => 'Acme Admin',
            'email' => 'admin@acme.com',
            'password' => hash_password('123456789'),
            'is_app_admin' => false,
            'is_tenant_admin' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ])->saveData();
        echo "Created Tenant Admin User: admin@acme.com\n";

        // --- Create the Super Admin User ---
        $superAdminId = $this->generateUuid();
        $this->table('users')->insert([
            'id' => $superAdminId,
            'tenant_id' => null,
            'name' => 'Super Admin',
            'email' => 'superadmin@dev.com',
            'password' => hash_password('123456789'),
            'is_app_admin' => true,
            'is_tenant_admin' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ])->saveData();
        echo "Created Super Admin User: superadmin@dev.com (Password: 123456789)\n";

        // --- 3. Define Core Permissions ---
        $permissions = [
            ['slug' => 'users.view', 'description' => 'View Users'],
            ['slug' => 'users.create', 'description' => 'Create Users'],
            ['slug' => 'users.edit', 'description' => 'Edit Users'],
            ['slug' => 'users.delete', 'description' => 'Delete Users'],
            ['slug' => 'roles.view', 'description' => 'View Roles'],
            ['slug' => 'roles.create', 'description' => 'Create Roles'],
            ['slug' => 'roles.edit', 'description' => 'Edit Roles'],
            ['slug' => 'roles.delete', 'description' => 'Delete Roles'],
            ['slug' => 'permissions.assign', 'description' => 'Assign Permissions to Roles'],
        ];
        foreach ($permissions as &$p) {
            $p['created_at'] = date('Y-m-d H:i:s');
        }
        $this->table('permissions')->insert($permissions)->saveData();
        echo "Created " . count($permissions) . " core permissions\n";

        $createdPermissions = $this->fetchAll('SELECT id FROM permissions');

        // --- 4. Create an Administrator Role for the Tenant ---
        $this->table('roles')->insert([
            'tenant_id' => $tenantId,
            'name' => 'Administrator',
            'description' => 'Has all permissions for the tenant.',
            'created_at' => date('Y-m-d H:i:s'),
        ])->saveData();

        // --- THIS IS THE FULLY CORRECTED QUERY ---
        // Use the adapter's query method for safe parameter binding
        $stmt = $this->getAdapter()->query(
            "SELECT id FROM roles WHERE name = ? AND tenant_id = ?",
            ['Administrator', $tenantId]
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $adminRoleId = $row ? $row['id'] : null;

        if (!$adminRoleId) {
            throw new \RuntimeException('Could not find the Administrator role after creating it.');
        }
        echo "Created Role: Administrator for Acme Corporation\n";

        // --- 5. Assign Role and Permissions ---
        $this->table('user_roles')->insert([
            'user_id' => $tenantAdminId,
            'role_id' => $adminRoleId,
        ])->saveData();
        echo "Assigned Administrator role to Acme's Tenant Admin\n";

        $rolePermissions = [];
        foreach ($createdPermissions as $permission) {
            $rolePermissions[] = [
                'role_id' => $adminRoleId,
                'permission_id' => $permission['id'],
            ];
        }
        if (!empty($rolePermissions)) {
            $this->table('role_permissions')->insert($rolePermissions)->saveData();
            echo "Assigned all permissions to the Administrator role\n";
        }
    }
}