<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserProfileAssetResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('user');
        Schema::dropIfExists('user_group');

        Schema::create('user_group', function (Blueprint $table) {
            $table->unsignedInteger('user_group_id')->primary();
            $table->boolean('is_admin')->default(false);
        });

        Schema::create('user', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary();
            $table->unsignedInteger('user_group_id');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('avatar', 2000)->nullable();
            $table->string('cover', 2000)->nullable();
            $table->timestamp('update_time')->nullable();
            $table->timestamp('disable_time')->nullable();
            $table->timestamp('disable_until')->nullable();
        });

        DB::table('user_group')->insert([
            ['user_group_id' => 1, 'is_admin' => true],
            ['user_group_id' => 2, 'is_admin' => false],
        ]);
        DB::table('user')->insert([
            [
                'user_id' => 1,
                'user_group_id' => 2,
                'username' => 'owner',
                'email' => 'owner@example.com',
                'password' => 'password',
            ],
            [
                'user_id' => 2,
                'user_group_id' => 2,
                'username' => 'other',
                'email' => 'other@example.com',
                'password' => 'password',
            ],
            [
                'user_id' => 3,
                'user_group_id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => 'password',
            ],
        ]);
    }

    public function test_user_can_reset_their_own_avatar(): void
    {
        $response = $this->resetAsset(actorId: 1, targetId: 1, type: 'avatar');
        $avatar = $response->getData(true)['user']['avatar'];

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $avatar);
        $this->assertSame($avatar, User::query()->findOrFail(1)->avatar);
    }

    public function test_admin_can_reset_another_users_cover(): void
    {
        $response = $this->resetAsset(actorId: 3, targetId: 2, type: 'cover');
        $cover = $response->getData(true)['user']['cover'];

        $this->assertStringStartsWith('/assets/material/', $cover);
        $this->assertFileExists(public_path(ltrim($cover, '/')));
        $this->assertSame($cover, User::query()->findOrFail(2)->cover);
    }

    public function test_non_admin_cannot_reset_another_users_profile_asset(): void
    {
        $this->expectException(HttpException::class);

        $this->resetAsset(actorId: 1, targetId: 2, type: 'cover');
    }

    private function resetAsset(int $actorId, int $targetId, string $type)
    {
        $request = Request::create(
            "/api/users/{$targetId}/reset-profile-asset",
            'POST',
            ['type' => $type],
        );
        $request->setUserResolver(fn () => User::query()->findOrFail($actorId));

        return (new UserController())->resetProfileAsset(
            $request,
            User::query()->findOrFail($targetId),
        );
    }
}
