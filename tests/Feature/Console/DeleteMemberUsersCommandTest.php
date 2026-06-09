<?php

namespace Tests\Feature\Console;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiRouteTestCase;

class DeleteMemberUsersCommandTest extends ApiRouteTestCase
{
    public function testTenantScopedDeletionRemovesOnlyItsMemberUsersAndKeepsGlobalRole(): void
    {
        $memberRole = $this->createRole('member');
        $memberUser = $this->createUser(role: $memberRole);
        $member = $this->createMember($memberUser);
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-delete-users',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $otherTenantUser = $this->createUser(attributes: ['tenant_id' => $otherTenant->id], role: $memberRole);

        $exitCode = Artisan::call('legacy:delete-member-users', [
            '--tenant-id' => $this->tenant->id,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertNull($member->fresh()->user_id);
        $this->assertNull(User::find($memberUser->id));
        $this->assertNotNull(User::find($otherTenantUser->id));
        $this->assertNotNull(Role::find($memberRole->id));
    }

    public function testGlobalDeletionRemovesMemberRoleAndLinkedUsers(): void
    {
        $memberRole = $this->createRole('member');
        $memberUser = $this->createUser(role: $memberRole);
        $member = $this->createMember($memberUser);

        $this->assertSame(0, Artisan::call('legacy:delete-member-users', ['--force' => true]));

        $this->assertNull($member->fresh()->user_id);
        $this->assertNull(User::find($memberUser->id));
        $this->assertNull(Role::find($memberRole->id));
    }

    public function testDeletionSucceedsWhenMemberRoleDoesNotExist(): void
    {
        $this->assertSame(0, Artisan::call('legacy:delete-member-users', ['--force' => true]));
    }
}
