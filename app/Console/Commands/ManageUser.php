<?php
// app/Console/Commands/ManageUser.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class ManageUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:manage 
                            {action : Action to perform (list|create|activate|deactivate|reset)}
                            {username? : Username for specific actions}
                            {--role= : Role for user creation}
                            {--password= : Password for user creation}
                            {--name= : Full name for user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage users for API authentication system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $username = $this->argument('username');

        switch ($action) {
            case 'list':
                $this->listUsers();
                break;
            case 'create':
                $this->createUser($username);
                break;
            case 'activate':
                $this->toggleUser($username, true);
                break;
            case 'deactivate':
                $this->toggleUser($username, false);
                break;
            case 'reset':
                $this->resetPassword($username);
                break;
            default:
                $this->error('Invalid action. Available actions: list, create, activate, deactivate, reset');
        }
    }

    /**
     * List all users
     */
    private function listUsers()
    {
        $users = User::with('role')->get();

        if ($users->isEmpty()) {
            $this->info('No users found.');
            return;
        }

        $this->info('Users in system:');
        $this->info('================');

        foreach ($users as $user) {
            $status = isset($user->is_active) ? ($user->is_active ? 'Active' : 'Inactive') : 'Unknown';
            $role = $user->role ? $user->role->name : 'No Role';
            $name = $user->name ?: 'Not set';
            $apiConnected = $user->api_user_id ? 'Yes' : 'No';

            $this->line(sprintf(
                '- Username: %s | Name: %s | Role: %s | Status: %s | API Connected: %s',
                $user->username,
                $name,
                $role,
                $status,
                $apiConnected
            ));
        }

        $this->info('================');
        $this->info('Total users: ' . $users->count());
    }

    /**
     * Create new user
     */
    private function createUser($username)
    {
        if (!$username) {
            $username = $this->ask('Enter username');
        }

        // Check if user already exists
        if (User::where('username', $username)->exists()) {
            $this->error("User '{$username}' already exists!");
            return;
        }

        // Get role
        $roleName = $this->option('role');
        if (!$roleName) {
            $this->info('Available roles:');
            $roles = Role::all();
            foreach ($roles as $role) {
                $this->line('- ' . $role->name);
            }
            $roleName = $this->ask('Enter role name', 'viewer');
        }

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("Role '{$roleName}' not found!");
            return;
        }

        // Get password
        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Enter initial password (min 6 characters)');
            if (strlen($password) < 6) {
                $this->error('Password must be at least 6 characters!');
                return;
            }
        }

        // Get name (optional)
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter full name (optional, can be updated from API)');
        }

        // Create user
        try {
            $user = User::create([
                'username' => $username,
                'password' => Hash::make($password),
                'role_id' => $role->id,
                'name' => $name ?: null,
                'email' => null,
                'api_user_id' => null,
                'is_active' => true,
            ]);

            $this->info("User '{$username}' created successfully!");
            $this->info("Role: {$role->name}");
            $this->info("Status: Active");
            $this->info("The user can now login with their KSP credentials.");

        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user active status
     */
    private function toggleUser($username, $active)
    {
        if (!$username) {
            $username = $this->ask('Enter username');
        }

        $user = User::where('username', $username)->first();
        if (!$user) {
            $this->error("User '{$username}' not found!");
            return;
        }

        try {
            $user->update(['is_active' => $active]);
            $status = $active ? 'activated' : 'deactivated';
            $this->info("User '{$username}' has been {$status}.");
        } catch (\Exception $e) {
            $this->error('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Reset user password
     */
    private function resetPassword($username)
    {
        if (!$username) {
            $username = $this->ask('Enter username');
        }

        $user = User::where('username', $username)->first();
        if (!$user) {
            $this->error("User '{$username}' not found!");
            return;
        }

        $password = $this->secret('Enter new password (min 6 characters)');
        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters!');
            return;
        }

        try {
            $user->update(['password' => Hash::make($password)]);
            $this->info("Password for user '{$username}' has been reset.");
            $this->info("Note: User will still login with their KSP credentials for API authentication.");
        } catch (\Exception $e) {
            $this->error('Failed to reset password: ' . $e->getMessage());
        }
    }
}