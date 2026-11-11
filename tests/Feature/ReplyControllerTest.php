<?php

namespace Tests\Feature;

use App\Http\Controllers\ReplyController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReplyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('vote');
        Schema::dropIfExists('follow');
        Schema::dropIfExists('answer');
        Schema::dropIfExists('reply');
        Schema::dropIfExists('comment');
        Schema::dropIfExists('user');
        Schema::dropIfExists('user_group');

        Schema::create('user_group', function (Blueprint $table) {
            $table->unsignedInteger('user_group_id')->primary();
            $table->string('user_group_name')->default('Member');
            $table->string('user_group_icon')->default('mdi-account');
            $table->string('user_group_color')->default('');
            $table->boolean('user_group_icon_show')->default(false);
            $table->boolean('ability_create_reply')->default(true);
        });

        Schema::create('user', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary();
            $table->unsignedInteger('user_group_id');
            $table->string('username');
            $table->string('email')->default('');
            $table->string('password')->default('');
            // 内容计数冗余字段（ReplyController 创建回复时会同步 reply_count）
            $table->unsignedInteger('topic_count')->default(0);
            $table->unsignedInteger('article_count')->default(0);
            $table->unsignedInteger('question_count')->default(0);
            $table->unsignedInteger('answer_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
        });

        Schema::create('comment', function (Blueprint $table) {
            $table->unsignedInteger('comment_id')->primary();
            $table->unsignedInteger('commentable_id');
            $table->string('commentable_type');
            $table->unsignedInteger('user_id');
            $table->text('content');
            $table->integer('reply_count')->default(0);
        });

        Schema::create('reply', function (Blueprint $table) {
            $table->increments('reply_id');
            $table->unsignedInteger('replyable_id');
            $table->string('replyable_type');
            $table->unsignedInteger('replyable_comment_id');
            $table->unsignedInteger('replyable_user_id')->default(0);
            $table->unsignedInteger('user_id');
            $table->text('content');
            $table->integer('reply_count')->default(0);
            $table->integer('vote_count')->default(0);
            $table->integer('vote_up_count')->default(0);
            $table->integer('vote_down_count')->default(0);
            $table->timestamp('create_time')->useCurrent();
            $table->timestamp('update_time')->useCurrent();
            $table->timestamp('delete_time')->nullable();
        });

        Schema::create('follow', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->string('followable_type');
            $table->unsignedInteger('followable_id');
        });

        Schema::create('answer', function (Blueprint $table) {
            $table->unsignedInteger('answer_id')->primary();
            $table->unsignedInteger('user_id');
        });

        Schema::create('vote', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->string('votable_type');
            $table->unsignedInteger('votable_id');
            $table->string('type');
        });
    }

    public function test_created_reply_response_includes_database_created_time(): void
    {
        DB::table('user_group')->insert([
            'user_group_id' => 1,
            'ability_create_reply' => true,
        ]);
        DB::table('user')->insert([
            'user_id' => 1,
            'user_group_id' => 1,
            'username' => 'reply-author',
        ]);
        DB::table('comment')->insert([
            'comment_id' => 1,
            'commentable_id' => 1,
            'commentable_type' => 'article',
            'user_id' => 1,
            'content' => 'Comment',
        ]);

        $request = Request::create('/api/replies', 'POST', [
            'replyable_id' => 1,
            'replyable_type' => 'reply',
            'replyable_comment_id' => 1,
            'replyable_user_id' => 1,
            'content' => 'Reply to reply',
        ]);
        $request->setUserResolver(fn () => User::query()->findOrFail(1));

        $response = (new ReplyController())->store($request);
        $data = $response->getData(true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertNotEmpty($data['create_time'] ?? null);

        // 作者的回复计数应同步递增
        $this->assertSame(1, (int) DB::table('user')->where('user_id', 1)->value('reply_count'));
    }
}
