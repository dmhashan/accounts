<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteMemberUsersCommand extends Command
{
    protected $signature = 'legacy:delete-member-users
        {--tenant-id= : Limit deletion to a specific tenant ID}
        {--force : Skip confirmation prompt}';

    protected $description = 'Delete all users with the member role, nullify member.user_id links, then delete the member role';

    public function handle(): int
    {
        $tenantId = $this->option('tenant-id');

        $memberRole = Role::where('slug', 'member')->first();

        if (!$memberRole) {
            $this->warn('No role with slug "member" found. Nothing to delete.');
            return self::SUCCESS;
        }

        $userQuery = User::where('role_id', $memberRole->id);
        if ($tenantId) {
            $userQuery->where('tenant_id', (int) $tenantId);
        }

        $userCount = $userQuery->count();

        $this->line("Found {$userCount} user(s) with the member role.");
        $this->line("Role to delete after: \"{$memberRole->name}\" (id={$memberRole->id})");

        if ($userCount === 0 && !$this->confirm('No member users found. Still delete the member role?', false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Delete {$userCount} user(s) and the member role? This cannot be undone.", false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($userQuery, $memberRole, $tenantId) {
            // Nullify member.user_id for any member linked to these users
            $userIds = (clone $userQuery)->pluck('id');

            if ($userIds->isNotEmpty()) {
                Member::whereIn('user_id', $userIds)->update(['user_id' => null]);
                $this->line('Nullified user_id on linked member records.');
            }

            $deleted = (clone $userQuery)->delete();
            $this->line("Deleted {$deleted} user(s).");

            // Only delete the role if we're not scoping to a single tenant
            // (roles are global, so only delete when running globally)
            if (!$tenantId) {
                $memberRole->permissions()->detach();
                $memberRole->delete();
                $this->line('Deleted member role and its permission links.');
            } else {
                $this->warn('Skipping role deletion because --tenant-id was specified (roles are global).');
            }
        });

        $this->info('Done.');
        return self::SUCCESS;
    }
}
