<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminModuleController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminModuleUserDisableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('user');
        Schema::dropIfExists('user_group');

        Schema::create('user_group', function (Blueprint $table) {
            $table->increments('user_group_id');
            $table->string('user_group_name');
            $table->unsignedInteger('user_group_user_count')->default(0);
            $table->timestamp('update_time')->nullable();
        });

        Schema::create('user', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('user_group_id');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->timestamp('create_time')->nullable();
            $table->timestamp('update_time')->nullable();
            $table->timestamp('disable_time')->nullable();
            $table->dateTime('disable_until')->nullable();
        });
    }

    public function test_user_can_be_disabled_and_enabled_through_module_update(): void
    {
        \DB::table('user_group')->insert([
            'user_group_id' => 1,
            'user_group_name' => 'Members',
        ]);

        $user = User::query()->create([
            'user_group_id' => 1,
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
        ]);

        $controller = new AdminModuleController();
        $disabledAt = now()->toISOString();
        $disableUntil = now()->addDays(2)->toDateString();
        $disableResponse = $controller->update(
            'users',
            $user->user_id,
            Request::create('/admin/modules/users/'.$user->user_id, 'PUT', [
                'disable_time' => $disabledAt,
                'disable_until' => $disableUntil,
            ]),
        );

        $this->assertSame(200, $disableResponse->getStatusCode());
        $this->assertNotNull($user->fresh()->disable_time);
        $this->assertSame($disableUntil.' 23:59:59', $user->fresh()->disable_until->toDateTimeString());
        $this->assertTrue($user->fresh()->isDisabled());
        $this->assertSame(0, (int) \DB::table('user_group')->where('user_group_id', 1)->value('user_group_user_count'));

        $enableResponse = $controller->update(
            'users',
            $user->user_id,
            Request::create('/admin/modules/users/'.$user->user_id, 'PUT', [
                'disable_time' => null,
                'disable_until' => null,
            ]),
        );

        $this->assertSame(200, $enableResponse->getStatusCode());
        $this->assertNull($user->fresh()->disable_time);
        $this->assertSame(1, (int) \DB::table('user_group')->where('user_group_id', 1)->value('user_group_user_count'));
    }

    public function test_expired_disables_are_treated_as_enabled(): void
    {
        \DB::table('user_group')->insert([
            'user_group_id' => 1,
            'user_group_name' => 'Members',
        ]);

        $user = User::query()->create([
            'user_group_id' => 1,
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
            'disable_time' => now()->subDays(2),
            'disable_until' => now()->subDay(),
        ]);

        $this->assertFalse($user->isDisabled());
        $this->assertNull($user->fresh()->disable_time);
        $this->assertNull($user->fresh()->disable_until);
        $this->assertSame(1, User::query()->whereNotDisabled()->count());
        $this->assertSame(1, (int) \DB::table('user_group')->where('user_group_id', 1)->value('user_group_user_count'));
    }

    public function test_disable_until_can_be_set_beyond_mysql_timestamp_range(): void
    {
        \DB::table('user_group')->insert([
            'user_group_id' => 1,
            'user_group_name' => 'Members',
        ]);

        $user = User::query()->create([
            'user_group_id' => 1,
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
        ]);

        $disableUntil = '2078-12-31';
        $response = (new AdminModuleController())->update(
            'users',
            $user->user_id,
            Request::create('/admin/modules/users/'.$user->user_id, 'PUT', [
                'disable_time' => now()->toISOString(),
                'disable_until' => $disableUntil,
            ]),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($disableUntil.' 23:59:59', $user->fresh()->disable_until->toDateTimeString());
    }
}
