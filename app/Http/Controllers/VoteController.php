<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    /**
     * 投票目标类型 → 目标表名 / 主键列名
     *
     * @var array<string, array{table: string, column: string}>
     */
    private const TYPE_MAP = [
        'question' => ['table' => 'question', 'column' => 'question_id'],
        'answer'   => ['table' => 'answer',   'column' => 'answer_id'],
        'article'  => ['table' => 'article',  'column' => 'article_id'],
        'comment'  => ['table' => 'comment',  'column' => 'comment_id'],
        'reply'    => ['table' => 'reply',    'column' => 'reply_id'],
    ];

    public function index()
    {
        return response()->json(DB::table('vote')->orderBy('create_time')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'required|integer',
            'votable_id'   => 'required|integer',
            'votable_type' => 'required|string|in:question,answer,article,comment,reply',
            'type'         => 'required|string|in:up,down',
        ]);

        DB::table('vote')->insert($validated);

        return response()->json(['inserted' => true], 201);
    }

    public function show(Request $request)
    {
        return response()->json(DB::table('vote')->where($request->query())->get());
    }

    /**
     * 切换投票（需登录）。
     *
     * 规则：
     * - 未投过      → 新增投票（up / down）
     * - 已投相同类型 → 取消投票
     * - 已投相反类型 → 切换为当前类型
     *
     * 同时重算目标内容的 vote_up_count / vote_down_count / vote_count，
     * 返回最新计数与当前用户投票状态，前端可直接覆盖本地状态。
     */
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'votable_type' => 'required|string|in:question,answer,article,comment,reply',
            'votable_id'   => 'required|integer',
            'type'         => 'required|string|in:up,down',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $type = $validated['votable_type'];
        $id = (int) $validated['votable_id'];
        $voteType = $validated['type'];

        $target = self::TYPE_MAP[$type];

        // 目标必须存在且未删除
        $exists = DB::table($target['table'])
            ->where($target['column'], $id)
            ->whereNull('delete_time')
            ->exists();

        if (! $exists) {
            return response()->json(['message' => 'Target not found.'], 404);
        }

        $existing = DB::table('vote')
            ->where('user_id', $user->user_id)
            ->where('votable_type', $type)
            ->where('votable_id', $id)
            ->first();

        DB::transaction(function () use ($existing, $user, $type, $id, $voteType, $target) {
            if ($existing) {
                if ($existing->type === $voteType) {
                    // 已投相同类型 → 取消投票
                    DB::table('vote')
                        ->where('user_id', $user->user_id)
                        ->where('votable_type', $type)
                        ->where('votable_id', $id)
                        ->delete();
                } else {
                    // 已投相反类型 → 切换
                    DB::table('vote')
                        ->where('user_id', $user->user_id)
                        ->where('votable_type', $type)
                        ->where('votable_id', $id)
                        ->update(['type' => $voteType]);
                }
            } else {
                DB::table('vote')->insert([
                    'user_id'      => $user->user_id,
                    'votable_id'   => $id,
                    'votable_type' => $type,
                    'type'         => $voteType,
                    'create_time'  => now(),
                ]);
            }

            // 重算目标内容计数
            $up = DB::table('vote')
                ->where('votable_type', $type)
                ->where('votable_id', $id)
                ->where('type', 'up')
                ->count();
            $down = DB::table('vote')
                ->where('votable_type', $type)
                ->where('votable_id', $id)
                ->where('type', 'down')
                ->count();

            DB::table($target['table'])
                ->where($target['column'], $id)
                ->update([
                    'vote_up_count'   => $up,
                    'vote_down_count' => $down,
                    'vote_count'      => $up - $down,
                ]);
        });

        // 新增点赞（第一次 up，或由 down 切换为 up）→ 发送点赞通知
        if ($voteType === 'up' && (! $existing || $existing->type !== 'up')) {
            $this->notifyLike($user->user_id, $type, $id);
        }

        // 当前用户最终的投票类型集合
        $myTypes = DB::table('vote')
            ->where('user_id', $user->user_id)
            ->where('votable_type', $type)
            ->where('votable_id', $id)
            ->pluck('type')
            ->all();

        $upCount = DB::table('vote')
            ->where('votable_type', $type)
            ->where('votable_id', $id)
            ->where('type', 'up')
            ->count();
        $downCount = DB::table('vote')
            ->where('votable_type', $type)
            ->where('votable_id', $id)
            ->where('type', 'down')
            ->count();

        return response()->json([
            'up' => [
                'count' => $upCount,
                'value' => in_array('up', $myTypes, true),
            ],
            'down' => [
                'count' => $downCount,
                'value' => in_array('down', $myTypes, true),
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        DB::table('vote')->where($request->query())->delete();

        return response()->json(null, 204);
    }

    /**
     * 点赞成功后向内容作者发送通知：
     * - article → article_like
     * - answer  → answer_like
     * - comment → comment_like
     * - reply   → reply_like
     * question 没有对应的点赞通知类型，跳过。
     */
    private function notifyLike(int $senderId, string $targetType, int $targetId): void
    {
        if ($targetType === 'question') {
            return;
        }

        $target = self::TYPE_MAP[$targetType];
        $row = DB::table($target['table'])
            ->where($target['column'], $targetId)
            ->first();

        if (! $row) {
            return;
        }

        $notifyType = null;
        $context = [];

        switch ($targetType) {
            case 'article':
                $notifyType = NotificationService::TYPE_ARTICLE_LIKE;
                $context['article_id'] = $targetId;
                break;

            case 'answer':
                $notifyType = NotificationService::TYPE_ANSWER_LIKE;
                $context['answer_id'] = $targetId;
                $context['question_id'] = (int) ($row->question_id ?? 0);
                break;

            case 'comment':
                $notifyType = NotificationService::TYPE_COMMENT_LIKE;
                $context = NotificationService::contextForComment($targetId);
                break;

            case 'reply':
                $notifyType = NotificationService::TYPE_REPLY_LIKE;
                $context = $this->replyContext($targetId);
                break;

            default:
                return;
        }

        NotificationService::send((int) $row->user_id, $senderId, $notifyType, $context);
    }

    /**
     * 解析回复所属的评论上下文（评论→所属文章/提问/回答），用于跳转定位。
     */
    private function replyContext(int $replyId): array
    {
        $reply = DB::table('reply')->where('reply_id', $replyId)->first();
        if (! $reply) {
            return ['reply_id' => $replyId];
        }

        $context = NotificationService::contextForComment((int) $reply->replyable_comment_id);
        $context['reply_id'] = $replyId;

        return $context;
    }
}
