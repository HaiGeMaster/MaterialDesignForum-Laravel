<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    /**
     * 幂等问题种子：不存在则插入，存在则更新。
     * 各条数据在 vote_count / answer_count / create_time 上有明确区分度，
     * 保证「热门」与「最新」排序结果不同。
     */
    public function run(): void
    {
        $questions = [
            [
                'question_id' => 1,
                'user_id' => 1,
                'title' => '如何在 Laravel 中实现全文搜索？',
                'content_markdown' => "项目里的帖子内容越来越多，用 LIKE 查询已经很慢了。想用 Scout + TNTSearch 或 Meilisearch 做全文搜索，有没有踩过坑的同学分享一下经验？",
                'content_rendered' => '<p>项目里的帖子内容越来越多，用 LIKE 查询已经很慢了。想用 Scout + TNTSearch 或 Meilisearch 做全文搜索，有没有踩过坑的同学分享一下经验？</p>',
                'comment_count' => 3,
                'answer_count' => 5,
                'follower_count' => 12,
                'vote_count' => 8,
                'vote_up_count' => 9,
                'vote_down_count' => 1,
                'last_answer_time' => '2026-08-16 10:00:00',
                'create_time' => '2026-07-15 08:30:00',
            ],
            [
                'question_id' => 2,
                'user_id' => 2,
                'title' => '论坛用户权限体系应该怎么设计？',
                'content_markdown' => "普通用户、版主、管理员三级权限，还要支持封禁和举报处理。是直接在 user 表加 role 字段，还是单独建权限表？哪种方案更利于后续扩展？",
                'content_rendered' => '<p>普通用户、版主、管理员三级权限，还要支持封禁和举报处理。是直接在 user 表加 role 字段，还是单独建权限表？哪种方案更利于后续扩展？</p>',
                'comment_count' => 6,
                'answer_count' => 8,
                'follower_count' => 21,
                'vote_count' => 15,
                'vote_up_count' => 16,
                'vote_down_count' => 1,
                'last_answer_time' => '2026-08-15 15:20:00',
                'create_time' => '2026-07-22 10:00:00',
            ],
            [
                'question_id' => 3,
                'user_id' => 3,
                'title' => 'Redis 做缓存有哪些最佳实践？',
                'content_markdown' => "缓存穿透、击穿、雪崩要怎么预防？热点数据过期策略怎么设计？缓存和数据库的一致性如何保证？求一份可以落地的方案。",
                'content_rendered' => '<p>缓存穿透、击穿、雪崩要怎么预防？热点数据过期策略怎么设计？缓存和数据库的一致性如何保证？求一份可以落地的方案。</p>',
                'comment_count' => 9,
                'answer_count' => 12,
                'follower_count' => 35,
                'vote_count' => 23,
                'vote_up_count' => 25,
                'vote_down_count' => 2,
                'last_answer_time' => '2026-08-14 20:00:00',
                'create_time' => '2026-07-30 14:20:00',
            ],
            [
                'question_id' => 4,
                'user_id' => 4,
                'title' => '单表数据量过大，如何做分表？',
                'content_markdown' => "消息表已经上千万行了，按月分表还是按用户 ID 取模？分表之后跨表查询和分页怎么做？有没有成熟的分库分表中间件推荐？",
                'content_rendered' => '<p>消息表已经上千万行了，按月分表还是按用户 ID 取模？分表之后跨表查询和分页怎么做？有没有成熟的分库分表中间件推荐？</p>',
                'comment_count' => 2,
                'answer_count' => 3,
                'follower_count' => 8,
                'vote_count' => 5,
                'vote_up_count' => 5,
                'vote_down_count' => 0,
                'last_answer_time' => '2026-08-13 09:30:00',
                'create_time' => '2026-08-08 09:45:00',
            ],
            [
                'question_id' => 5,
                'user_id' => 5,
                'title' => 'WebSocket 实时通知怎么接入？',
                'content_markdown' => "想要实现新消息、新回复的实时提醒，用 Laravel Reverb 还是 Pusher？前端用原生 WebSocket 还是 socket.io？需要注意哪些坑？",
                'content_rendered' => '<p>想要实现新消息、新回复的实时提醒，用 Laravel Reverb 还是 Pusher？前端用原生 WebSocket 还是 socket.io？需要注意哪些坑？</p>',
                'comment_count' => 4,
                'answer_count' => 6,
                'follower_count' => 17,
                'vote_count' => 12,
                'vote_up_count' => 13,
                'vote_down_count' => 1,
                'last_answer_time' => '2026-08-15 18:40:00',
                'create_time' => '2026-08-14 16:10:00',
            ],
        ];

        foreach ($questions as $question) {
            DB::table('question')->updateOrInsert(
                ['question_id' => $question['question_id']],
                [
                    'user_id' => $question['user_id'],
                    'title' => $question['title'],
                    'content_markdown' => $question['content_markdown'],
                    'content_rendered' => $question['content_rendered'],
                    'comment_count' => $question['comment_count'],
                    'answer_count' => $question['answer_count'],
                    'follower_count' => $question['follower_count'],
                    'vote_count' => $question['vote_count'],
                    'vote_up_count' => $question['vote_up_count'],
                    'vote_down_count' => $question['vote_down_count'],
                    'last_answer_time' => $question['last_answer_time'],
                    'create_time' => $question['create_time'],
                    'update_time' => $question['create_time'],
                    'delete_time' => null,
                ],
            );
        }
    }
}
