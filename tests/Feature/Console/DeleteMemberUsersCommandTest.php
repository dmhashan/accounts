<?php

namespace Tests\Feature\Console;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Api\ApiRouteTestCase;

class DeleteMemberUsersCommandTest extends ApiRouteTestCase
{
    public function testTenantScopedDeletionRemovesMemberUsersAndKeepsGlobalRoleWhenIsolationIsDisabled(): void
    {
        $memberRole = $this->createRole('member');
        $memberUser = $this->createUser(role: $memberRole);
        $member = $this->createMember($memberUser);

        $exitCode = Artisan::call('legacy:delete-member-users', [
            '--tenant-id' => $this->tenant->id,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertNull($member->fresh()->user_id);
        $this->assertNull(User::find($memberUser->id));
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
