<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Reply;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    /**
     * 回复列表：按所属评论过滤，可选分页 / 排序。
     *
     * GET /replies?replyable_comment_id=1&order=-create_time&page=1
     * 附带作者（creator）、回复目标用户（replyable_user）、当前用户投票状态（my_vote）。
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'replyable_comment_id' => 'required|integer',
        ]);

        $query = Reply::query()
            ->where('replyable_comment_id', $validated['replyable_comment_id'])
            ->whereNull('delete_time');

        // 排序：-create_time 最新优先（默认）、+create_time 最早优先
        match ($request->input('order')) {
            '+create_time' => $query->orderBy('create_time')->orderBy('reply_id'),
            default => $query->orderByDesc('create_time')->orderByDesc('reply_id'),
        };

        [$result, $collection] = $this->paginateOrGet($query, $request);

        $this->appendAuthors($collection, $request, 'user_id');
        $this->appendVoteStatus($collection, $request, 'reply', 'reply_id');
        $this->appendReplyableUsers($collection);

        return response()->json($result);
    }

    /**
     * 发布回复：可回复评论（replyable_type=comment），也可回复某条回复（replyable_type=reply），
     * 回复对回复通过 replyable_user_id 标记被回复的用户，前端展示“XX 回复了 XX”。
     *
     * POST /replies
     * 参数：replyable_id、replyable_type、replyable_comment_id、replyable_user_id、content
     */
    public function store(Request $request)
    {
        if (! $request->user()->hasAbility('ability_create_reply')) {
            return response()->json(['message' => __('messages.http_reply_1')], 403);
        }

        $validated = $request->validate([
            'replyable_id' => 'required|integer',
            'replyable_type' => 'required|string|in:comment,reply',
            'replyable_comment_id' => 'required|integer',
            'replyable_user_id' => 'nullable|integer',
            'content' => 'required|string|max:1000',
        ]);

        $reply = Reply::create([
            'replyable_id' => $validated['replyable_id'],
            'replyable_type' => $validated['replyable_type'],
            'replyable_comment_id' => $validated['replyable_comment_id'],
            'replyable_user_id' => $validated['replyable_user_id'] ?? 0,
            'user_id' => $request->user()->user_id,
            'content' => $validated['content'],
        ]);
        $reply->refresh();

        // 同步作者回复计数（user.reply_count）
        $this->adjustUserContentCount('reply', $reply->user_id);

        // 递增父评论的回复计数
        Comment::query()->whereKey($validated['replyable_comment_id'])->increment('reply_count');

        // 若回复的是另一条回复，同步父回复的 reply_count 冗余字段
        if ($validated['replyable_type'] === 'reply') {
            Reply::query()->whereKey($validated['replyable_id'])->increment('reply_count');
        }

        // 发送回复通知（comment_reply / reply_reply）
        $this->notifyReplyCreated($reply);

        $this->appendAuthors(collect([$reply]), $request, 'user_id');
        $this->appendVoteStatusToModel($reply, $request, 'reply');
        $this->appendReplyableUsers(collect([$reply]));

        return response()->json($reply, 201);
    }

    /**
     * 编辑回复：仅回复作者本人，可修改内容。
     */
    public function update(Request $request, Reply $reply)
    {
        $user = $request->user();
        if (! $user || $user->user_id !== $reply->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $reply->update(['content' => $validated['content']]);

        $this->appendAuthors(collect([$reply]), $request, 'user_id');
        $this->appendVoteStatusToModel($reply, $request, 'reply');
        $this->appendReplyableUsers(collect([$reply]));

        return response()->json($reply);
    }

    /**
     * 删除回复：仅回复作者本人，并递减父评论的回复计数。
     */
    public function destroy(Request $request, Reply $reply)
    {
        $user = $request->user();
        if (! $user || $user->user_id !== $reply->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // 删除前先记录回复目标类型，用于递减父回复的 reply_count
        $wasReplyToReply = $reply->replyable_type === 'reply';
        $parentReplyId = $wasReplyToReply ? (int) $reply->replyable_id : 0;

        // 已被后台软删除过的回复不再重复扣减作者计数
        $wasDeleted = $reply->delete_time !== null;

        Comment::query()
            ->whereKey($reply->replyable_comment_id)
            ->where('reply_count', '>', 0)
            ->decrement('reply_count');

        // 若被删除的是"对回复的回复"，同步递减父回复的 reply_count
        if ($wasReplyToReply && $parentReplyId > 0) {
            Reply::query()
                ->whereKey($parentReplyId)
                ->where('reply_count', '>', 0)
                ->decrement('reply_count');
        }

        $reply->delete();

        if (! $wasDeleted) {
            $this->adjustUserContentCount('reply', $reply->user_id, -1);
        }

        // 回复被删除时通知回复作者（reply_delete；作者本人删除时自动跳过）
        NotificationService::send(
            (int) $reply->user_id,
            $user->user_id,
            NotificationService::TYPE_REPLY_DELETE,
            ['reply_id' => (int) $reply->reply_id]
        );

        return response()->json(null, 204);
    }

    /**
     * 回复成功后发送通知：
     * - 回复评论（replyable_type=comment）→ 通知评论作者 comment_reply
     * - 回复回复（replyable_type=reply）  → 通知被回复的用户 reply_reply
     */
    private function notifyReplyCreated(Reply $reply): void
    {
        $senderId = (int) $reply->user_id;
        $commentId = (int) $reply->replyable_comment_id;

        $context = NotificationService::contextForComment($commentId);
        $context['reply_id'] = (int) $reply->reply_id;

        if ($reply->replyable_type === 'comment') {
            // 被回复的是评论：通知评论作者
            $receiverId = $reply->replyable_user_id > 0
                ? (int) $reply->replyable_user_id
                : (int) Comment::query()->whereKey($commentId)->value('user_id');

            NotificationService::send($receiverId, $senderId, NotificationService::TYPE_COMMENT_REPLY, $context);
        } else {
            // 被回复的是回复：通知被回复的用户
            $receiverId = $reply->replyable_user_id > 0
                ? (int) $reply->replyable_user_id
                : (int) Reply::query()->whereKey((int) $reply->replyable_id)->value('user_id');

            $context['reply_to_reply_id'] = (int) $reply->replyable_id;

            NotificationService::send($receiverId, $senderId, NotificationService::TYPE_REPLY_REPLY, $context);
        }
    }

    /**
     * 批量附加回复目标用户（replyable_user），用于展示“XX 回复了 XX”。
     */
    private function appendReplyableUsers($items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $ids = $items->pluck('replyable_user_id')->filter()->unique()->all();
        $users = $ids
            ? User::query()->whereIn('user_id', $ids)->get()->keyBy('user_id')
            : collect();

        $items->each(function ($reply) use ($users) {
            $reply->replyable_user = $reply->replyable_user_id
                ? ($users->get($reply->replyable_user_id) ?? null)
                : null;
        });
    }
}
