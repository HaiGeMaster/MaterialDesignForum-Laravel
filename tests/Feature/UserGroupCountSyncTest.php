<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminModuleController;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserGroupCountSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('user');
        Schema::dropIfExists('user_group');

        Schema::create('user_group', function (Blueprint $table) {
            $table->increments('user_group_id');
            $table->string('user_group_name');
            $table->string('user_group_description');
            $table->string('user_group_icon')->default('mdi-account');
            $table->string('user_group_color')->default('');
            $table->unsignedInteger('user_group_user_count')->default(0);
            $table->timestamp('create_time')->nullable();
            $table->timestamp('update_time')->nullable();
            $table->timestamp('delete_time')->nullable();
        });

        Schema::create('user', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('user_group_id')->default(2);
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->timestamp('create_time')->nullable();
            $table->timestamp('update_time')->nullable();
            $table->timestamp('last_login_time')->nullable();
            $table->timestamp('disable_time')->nullable();
        });
    }

    public function test_user_group_count_is_synced_for_user_creation_and_group_change(): void
    {
        $groupA = UserGroup::query()->create([
            'user_group_name' => 'Group A',
            'user_group_description' => 'Group A',
            'user_group_icon' => 'mdi-account',
            'user_group_color' => '#2196f3',
            'user_group_user_count' => 0,
            'create_time' => now(),
            'update_time' => now(),
        ]);

        $groupB = UserGroup::query()->create([
            'user_group_name' => 'Group B',
            'user_group_description' => 'Group B',
            'user_group_icon' => 'mdi-account',
            'user_group_color' => '#4CAF50',
            'user_group_user_count' => 0,
            'create_time' => now(),
            'update_time' => now(),
        ]);

        $user = User::query()->create([
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => bcrypt('password123'),
            'user_group_id' => $groupA->user_group_id,
            'create_time' => now(),
            'update_time' => now(),
            'last_login_time' => now(),
        ]);

        $this->assertSame(1, $groupA->fresh()->user_group_user_count);
        $this->assertSame(0, $groupB->fresh()->user_group_user_count);

        $user->update(['user_group_id' => $groupB->user_group_id]);

        $this->assertSame(0, $groupA->fresh()->user_group_user_count);
        $this->assertSame(1, $groupB->fresh()->user_group_user_count);
    }

    public function test_user_group_with_members_cannot_be_deleted_until_members_are_moved(): void
    {
        $group = UserGroup::query()->create([
            'user_group_name' => 'Occupied Group',
            'user_group_description' => 'Occupied Group',
            'user_group_icon' => 'mdi-account',
            'user_group_color' => '#FF9800',
            'user_group_user_count' => 0,
            'create_time' => now(),
            'update_time' => now(),
        ]);

        User::query()->create([
            'username' => 'occupied-user',
            'email' => 'occupied@example.com',
            'password' => bcrypt('password123'),
            'user_group_id' => $group->user_group_id,
            'create_time' => now(),
            'update_time' => now(),
            'last_login_time' => now(),
        ]);

        $this->assertSame(1, $group->fresh()->user_group_user_count);

        $response = (new AdminModuleController())->destroy('user-groups', $group->user_group_id, Request::create('/admin/modules/user-groups/'.$group->user_group_id, 'DELETE'));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('先将用户组中的成员', $response->getData(true)['message'] ?? '');
        $this->assertStringContainsString('用户管理', $response->getData(true)['message'] ?? '');
        $this->assertNotNull(UserGroup::query()->find($group->user_group_id));
    }
}
