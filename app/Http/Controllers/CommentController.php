<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Question;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * 评论列表：按被评论对象过滤，可选分页。
     * GET /comments?commentable_type=article&commentable_id=1&page=1
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string|in:article,question,answer',
            'commentable_id' => 'required|integer',
        ]);

        $query = Comment::query()
            ->where('commentable_type', $validated['commentable_type'])
            ->where('commentable_id', $validated['commentable_id'])
            ->whereNull('delete_time');

        // 排序：-create_time 最新优先（默认）、+create_time 最早优先、-vote_count 最多点赞
        match ($request->input('order')) {
            '+create_time' => $query->orderBy('create_time')->orderBy('comment_id'),
            '-vote_count' => $query->orderByDesc('vote_count')->orderByDesc('comment_id'),
            default => $query->orderByDesc('create_time')->orderByDesc('comment_id'),
        };

        [$result, $collection] = $this->paginateOrGet($query, $request);

        $this->appendAuthors($collection, $request, 'user_id');
        $this->appendVoteStatus($collection, $request, 'comment', 'comment_id');

        return response()->json($result);
    }

    /**
     * 发布评论：必须登录，评论归属当前用户，并递增所属内容的评论计数。
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string|in:article,question,answer',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'commentable_type' => $validated['commentable_type'],
            'commentable_id' => $validated['commentable_id'],
            'user_id' => $request->user()->user_id,
            'content' => $validated['content'],
        ]);

        $this->updateCommentCount($validated['commentable_type'], $validated['commentable_id'], 1);

        // 发送评论通知（article_comment / question_comment / answer_comment + 关注推送）
        $this->notifyCommentCreated($comment);

        $this->appendAuthors(collect([$comment]), $request, 'user_id');
        $this->appendVoteStatusToModel($comment, $request, 'comment');

        return response()->json($comment, 201);
    }

    /**
     * 编辑评论：仅评论作者本人，可修改内容。
     */
    public function update(Request $request, Comment $comment)
    {
        $user = $request->user();
        if ($user->user_id !== $comment->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update(['content' => $validated['content']]);

        $this->appendAuthors(collect([$comment]), $request, 'user_id');
        $this->appendVoteStatusToModel($comment, $request, 'comment');

        return response()->json($comment);
    }

    /**
     * 删除评论：仅评论作者本人，并递减所属内容的评论计数。
     */
    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();
        if ($user->user_id !== $comment->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $this->updateCommentCount($comment->commentable_type, $comment->commentable_id, -1);
        $comment->delete();

        // 评论被删除时通知评论作者（comment_delete；作者本人删除时自动跳过）
        NotificationService::send(
            (int) $comment->user_id,
            $user->user_id,
            NotificationService::TYPE_COMMENT_DELETE,
            ['comment_id' => (int) $comment->comment_id]
        );

        return response()->json(null, 204);
    }

    /**
     * 评论成功后发送通知：
     * - 通知被评论内容的作者（article_comment / question_comment / answer_comment）
     * - 关注推送：文章被评论 → 文章关注者（follow_article_update）；
     *             提问/回答被评论 → 提问关注者（follow_question_update）
     */
    private function notifyCommentCreated(Comment $comment): void
    {
        $type = $comment->commentable_type;
        $id = (int) $comment->commentable_id;
        $senderId = (int) $comment->user_id;

        $notifyType = match ($type) {
            'article' => NotificationService::TYPE_ARTICLE_COMMENT,
            'question' => NotificationService::TYPE_QUESTION_COMMENT,
            'answer' => NotificationService::TYPE_ANSWER_COMMENT,
            default => null,
        };

        $ownerId = match ($type) {
            'article' => (int) Article::query()->whereKey($id)->value('user_id'),
            'question' => (int) Question::query()->whereKey($id)->value('user_id'),
            'answer' => (int) Answer::query()->whereKey($id)->value('user_id'),
            default => 0,
        };

        if ($notifyType && $ownerId > 0) {
            $context = NotificationService::contextForContent($type, $id, [
                'comment_id' => (int) $comment->comment_id,
                'content_rendered' => $comment->content,
            ]);
            NotificationService::send($ownerId, $senderId, $notifyType, $context);
        }

        // 关注推送
        if ($type === 'article') {
            $followers = Follow::query()
                ->where('followable_type', 'article')
                ->where('followable_id', $id)
                ->pluck('user_id');
            NotificationService::sendToMany($followers, $senderId, NotificationService::TYPE_FOLLOW_ARTICLE_UPDATE, [
                'article_id' => $id,
            ]);
        } else {
            $questionId = $type === 'question'
                ? $id
                : (int) Answer::query()->whereKey($id)->value('question_id');
            if ($questionId > 0) {
                $followers = Follow::query()
                    ->where('followable_type', 'question')
                    ->where('followable_id', $questionId)
                    ->pluck('user_id');
                NotificationService::sendToMany($followers, $senderId, NotificationService::TYPE_FOLLOW_QUESTION_UPDATE, [
                    'question_id' => $questionId,
                ]);
            }
        }
    }

    /**
     * 同步被评论内容的评论计数（comment_count 冗余字段）。
     */
    private function updateCommentCount(string $type, int $id, int $delta): void
    {
        $model = match ($type) {
            'article' => Article::class,
            'question' => Question::class,
            'answer' => Answer::class,
            default => null,
        };
        if (! $model) {
            return;
        }

        $query = $model::query()->whereKey($id);
        if ($delta < 0) {
            $query->where('comment_count', '>', 0)->decrement('comment_count', abs($delta));
        } else {
            $query->increment('comment_count', $delta);
        }
    }
}
