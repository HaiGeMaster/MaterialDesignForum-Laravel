<?php

namespace App\Support;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Reply;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

/**
 * user 表内容计数冗余字段的维护工具。
 *
 * 涉及字段：topic_count / article_count / question_count /
 *           answer_count / comment_count / reply_count。
 *
 * 这些字段由各内容控制器在新增 / 删除时调用 adjust() 同步；
 * 历史数据或异常漂移可通过 `php artisan forum:recount-user-counts` 重算。
 */
class UserContentCounts
{
    /**
     * 内容类型 => user 表计数字段
     */
    public const COLUMNS = [
        'topic' => 'topic_count',
        'article' => 'article_count',
        'question' => 'question_count',
        'answer' => 'answer_count',
        'comment' => 'comment_count',
        'reply' => 'reply_count',
    ];

    /**
     * 内容类型 => 对应模型类
     */
    private const SOURCES = [
        'topic' => Topic::class,
        'article' => Article::class,
        'question' => Question::class,
        'answer' => Answer::class,
        'comment' => Comment::class,
        'reply' => Reply::class,
    ];

    /**
     * 按增量维护指定用户的内容计数。
     *
     * 递减时自动加 `column > 0` 条件，避免出现负数。
     *
     * @param  int|null  $userId  内容作者的用户 ID
     * @param  string    $column  计数字段名
     * @param  int       $delta   增量：+1 新增 / -1 删除
     */
    public static function adjust(?int $userId, string $column, int $delta = 1): void
    {
        if ($userId === null || $userId <= 0 || $delta === 0) {
            return;
        }

        if (! in_array($column, self::COLUMNS, true)) {
            return;
        }

        $query = DB::table('user')->where('user_id', $userId);

        if ($delta < 0) {
            $query->where($column, '>', 0)->decrement($column, abs($delta));
        } else {
            $query->increment($column, $delta);
        }
    }

    /**
     * 按内容表实时校准单个用户的 6 项内容计数。
     *
     * 用于登录 / 自动登录时的自愈：即使历史数据或并发写入造成漂移，
     * 也能在用户下次进入站点时被纠正。
     *
     * @return array<string, int> 计数字段 => 校准后的数值
     */
    public static function syncUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $data = [];
        foreach (self::SOURCES as $type => $modelClass) {
            $column = self::COLUMNS[$type];
            $data[$column] = $modelClass::query()
                ->where('user_id', $userId)
                ->whereNull('delete_time')
                ->count();
        }

        DB::table('user')->where('user_id', $userId)->update($data);

        return $data;
    }

    /**
     * 按内容表实时重算所有用户的 6 项内容计数。
     *
     * 统计口径：不含已软删除（delete_time 非空）的记录。
     *
     * @return array<string, int> 计数字段 => 参与统计的内容总条数
     */
    public static function recount(): array
    {
        // 每种内容按用户聚合一次，避免逐用户查询
        $counts = [];
        foreach (self::SOURCES as $type => $modelClass) {
            $column = self::COLUMNS[$type];
            $counts[$column] = $modelClass::query()
                ->whereNull('delete_time')
                ->selectRaw('user_id, COUNT(*) as cnt')
                ->groupBy('user_id')
                ->pluck('cnt', 'user_id');
        }

        // 逐用户一次性覆盖 6 个字段（含清零，修正历史脏数据）
        DB::table('user')
            ->select('user_id')
            ->orderBy('user_id')
            ->chunk(500, function ($users) use ($counts): void {
                foreach ($users as $user) {
                    $userId = (int) $user->user_id;
                    $data = [];
                    foreach ($counts as $column => $map) {
                        $data[$column] = (int) ($map[$userId] ?? 0);
                    }

                    DB::table('user')->where('user_id', $userId)->update($data);
                }
            });

        $totals = [];
        foreach ($counts as $column => $map) {
            $totals[$column] = (int) $map->sum();
        }

        return $totals;
    }
}
