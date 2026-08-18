<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleSeeder extends Seeder
{
    /**
     * 幂等文章种子：不存在则插入，存在则更新。
     */
    public function run(): void
    {
        $articles = [
            [
                'article_id' => 1,
                'user_id' => 1,
                'title' => 'Vue 3 Composition API 实战指南',
                'content_markdown' => "Vue 3 的 Composition API 让组件逻辑复用变得更加自然。本文从 setup 语法糖、响应式 API 到自定义组合式函数，梳理一份实战心得。",
                'content_rendered' => '<p>Vue 3 的 Composition API 让组件逻辑复用变得更加自然。本文从 setup 语法糖、响应式 API 到自定义组合式函数，梳理一份实战心得。</p>',
                'comment_count' => 12,
                'follower_count' => 86,
                'vote_count' => 42,
                'vote_up_count' => 45,
                'vote_down_count' => 3,
                'create_time' => '2026-07-20 09:00:00',
            ],
            [
                'article_id' => 2,
                'user_id' => 2,
                'title' => 'Laravel 队列系统的正确打开方式',
                'content_markdown' => "异步任务、失败重试、延迟执行，Laravel 队列比你想象的更强大。本文从队列驱动选型讲到生产环境监控。",
                'content_rendered' => '<p>异步任务、失败重试、延迟执行，Laravel 队列比你想象的更强大。本文从队列驱动选型讲到生产环境监控。</p>',
                'comment_count' => 9,
                'follower_count' => 64,
                'vote_count' => 35,
                'vote_up_count' => 37,
                'vote_down_count' => 2,
                'create_time' => '2026-07-25 14:30:00',
            ],
            [
                'article_id' => 3,
                'user_id' => 3,
                'title' => 'MySQL 索引优化从入门到实战',
                'content_markdown' => "最左前缀、覆盖索引、慢查询分析……索引优化是数据库性能的关键。这篇文章带你一步步排查线上慢 SQL。",
                'content_rendered' => '<p>最左前缀、覆盖索引、慢查询分析……索引优化是数据库性能的关键。这篇文章带你一步步排查线上慢 SQL。</p>',
                'comment_count' => 15,
                'follower_count' => 78,
                'vote_count' => 51,
                'vote_up_count' => 53,
                'vote_down_count' => 2,
                'create_time' => '2026-07-28 10:15:00',
            ],
            [
                'article_id' => 4,
                'user_id' => 4,
                'title' => '用 Docker 快速搭建本地开发环境',
                'content_markdown' => "docker-compose 一把梭：PHP、MySQL、Redis、Node 全都容器化，换电脑也能 5 分钟拉起整套环境。",
                'content_rendered' => '<p>docker-compose 一把梭：PHP、MySQL、Redis、Node 全都容器化，换电脑也能 5 分钟拉起整套环境。</p>',
                'comment_count' => 6,
                'follower_count' => 41,
                'vote_count' => 18,
                'vote_up_count' => 19,
                'vote_down_count' => 1,
                'create_time' => '2026-08-01 16:45:00',
            ],
            [
                'article_id' => 5,
                'user_id' => 5,
                'title' => 'Vite 5 构建性能调优实践',
                'content_markdown' => "依赖预构建、并行压缩、拆包策略……把一次 40 秒的冷启动优化到 8 秒，聊聊我们踩过的坑。",
                'content_rendered' => '<p>依赖预构建、并行压缩、拆包策略……把一次 40 秒的冷启动优化到 8 秒，聊聊我们踩过的坑。</p>',
                'comment_count' => 8,
                'follower_count' => 55,
                'vote_count' => 29,
                'vote_up_count' => 30,
                'vote_down_count' => 1,
                'create_time' => '2026-08-05 11:20:00',
            ],
            [
                'article_id' => 6,
                'user_id' => 1,
                'title' => '写给后端同学的前端工程化入门',
                'content_markdown' => "模块化、打包、代码规范、CI 检查——前端工程化并没有想象中那么玄乎，看完这篇文章你也能上手。",
                'content_rendered' => '<p>模块化、打包、代码规范、CI 检查——前端工程化并没有想象中那么玄乎，看完这篇文章你也能上手。</p>',
                'comment_count' => 5,
                'follower_count' => 33,
                'vote_count' => 15,
                'vote_up_count' => 16,
                'vote_down_count' => 1,
                'create_time' => '2026-08-10 09:30:00',
            ],
        ];

        foreach ($articles as $article) {
            DB::table('article')->updateOrInsert(
                ['article_id' => $article['article_id']],
                [
                    'user_id' => $article['user_id'],
                    'title' => $article['title'],
                    'content_markdown' => $article['content_markdown'],
                    'content_rendered' => $article['content_rendered'],
                    'comment_count' => $article['comment_count'],
                    'follower_count' => $article['follower_count'],
                    'vote_count' => $article['vote_count'],
                    'vote_up_count' => $article['vote_up_count'],
                    'vote_down_count' => $article['vote_down_count'],
                    'create_time' => $article['create_time'],
                    'update_time' => $article['create_time'],
                    'delete_time' => null,
                ],
            );
        }
    }
}
