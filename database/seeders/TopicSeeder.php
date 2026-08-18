<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TopicSeeder extends Seeder
{
    /**
     * 幂等话题种子：不存在则插入，存在则更新计数。
     */
    public function run(): void
    {
        $topics = [
            ['topic_id' => 1, 'name' => 'Vue.js', 'description' => 'Vue 3 生态、组件与最佳实践', 'article_count' => 48, 'question_count' => 80, 'follower_count' => 128],
            ['topic_id' => 2, 'name' => 'Laravel', 'description' => 'PHP 框架 Laravel 开发经验', 'article_count' => 36, 'question_count' => 60, 'follower_count' => 96],
            ['topic_id' => 3, 'name' => '数据库', 'description' => 'MySQL / SQLite / Redis 使用心得', 'article_count' => 24, 'question_count' => 40, 'follower_count' => 64],
            ['topic_id' => 4, 'name' => 'DevOps', 'description' => '部署、CI/CD 与容器化', 'article_count' => 18, 'question_count' => 27, 'follower_count' => 45],
            ['topic_id' => 5, 'name' => '前端工程化', 'description' => 'Vite、打包工具与性能优化', 'article_count' => 12, 'question_count' => 21, 'follower_count' => 33],
            ['topic_id' => 6, 'name' => '开发者生活', 'description' => '效率工具与日常分享', 'article_count' => 9, 'question_count' => 12, 'follower_count' => 21],
        ];

        foreach ($topics as $topic) {
            DB::table('topic')->updateOrInsert(
                ['topic_id' => $topic['topic_id']],
                [
                    'name' => $topic['name'],
                    'cover' => null,
                    'description' => $topic['description'],
                    'article_count' => $topic['article_count'],
                    'question_count' => $topic['question_count'],
                    'follower_count' => $topic['follower_count'],
                    'user_id' => 1,
                    'create_time' => now(),
                    'update_time' => now(),
                    'delete_time' => null,
                ],
            );
        }
    }
}
