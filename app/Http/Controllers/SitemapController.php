<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;

class SitemapController extends Controller
{
    /**
     * 动态生成 sitemap.xml
     */
    public function index()
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $urls = [];

        // ── 静态页面 ──
        $urls[] = $this->makeEntry("$baseUrl/", 'daily', '1.0');
        $urls[] = $this->makeEntry("$baseUrl/topics", 'daily', '0.8');
        $urls[] = $this->makeEntry("$baseUrl/questions", 'daily', '0.8');
        $urls[] = $this->makeEntry("$baseUrl/articles", 'daily', '0.8');
        $urls[] = $this->makeEntry("$baseUrl/users", 'weekly', '0.7');

        // ── 动态：话题详情 ──
        Topic::whereNull('delete_time')
            ->orderByDesc('update_time')
            ->limit(2000)
            ->select(['topic_id', 'update_time'])
            ->chunk(500, function ($topics) use ($baseUrl, &$urls) {
                foreach ($topics as $topic) {
                    $urls[] = $this->makeEntry(
                        "$baseUrl/topics/{$topic->topic_id}",
                        'weekly',
                        '0.6',
                        $topic->update_time
                    );
                }
            });

        // ── 动态：文章详情 ──
        Article::whereNull('delete_time')
            ->orderByDesc('update_time')
            ->limit(2000)
            ->select(['article_id', 'update_time'])
            ->chunk(500, function ($articles) use ($baseUrl, &$urls) {
                foreach ($articles as $article) {
                    $urls[] = $this->makeEntry(
                        "$baseUrl/articles/{$article->article_id}",
                        'weekly',
                        '0.6',
                        $article->update_time
                    );
                }
            });

        // ── 动态：问题详情 ──
        Question::whereNull('delete_time')
            ->orderByDesc('update_time')
            ->limit(2000)
            ->select(['question_id', 'update_time'])
            ->chunk(500, function ($questions) use ($baseUrl, &$urls) {
                foreach ($questions as $question) {
                    $urls[] = $this->makeEntry(
                        "$baseUrl/questions/{$question->question_id}",
                        'weekly',
                        '0.6',
                        $question->update_time
                    );
                }
            });

        // ── 动态：用户主页 ──
        User::whereNull('disable_time')
            ->orderByDesc('update_time')
            ->limit(2000)
            ->select(['user_id', 'username', 'update_time'])
            ->chunk(500, function ($users) use ($baseUrl, &$urls) {
                foreach ($users as $user) {
                    $urls[] = $this->makeEntry(
                        "$baseUrl/users/{$user->user_id}",
                        'monthly',
                        '0.5',
                        $user->update_time
                    );
                }
            });

        return response($this->renderXml($urls), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * 构建单个 sitemap 条目
     */
    private function makeEntry(string $loc, string $changefreq, string $priority, $updateTime = null): array
    {
        $entry = [
            'loc'        => $loc,
            'changefreq' => $changefreq,
            'priority'   => $priority,
        ];

        if ($updateTime) {
            $entry['lastmod'] = $updateTime instanceof \DateTimeInterface
                ? $updateTime->format('Y-m-d')
                : date('Y-m-d');
        }

        return $entry;
    }

    /**
     * 渲染 XML
     */
    private function renderXml(array $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . '</loc>';
            if (!empty($url['lastmod'])) {
                $lines[] = '    <lastmod>' . $url['lastmod'] . '</lastmod>';
            }
            $lines[] = '    <changefreq>' . $url['changefreq'] . '</changefreq>';
            $lines[] = '    <priority>' . $url['priority'] . '</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }
}
