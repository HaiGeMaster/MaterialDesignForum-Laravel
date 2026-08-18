<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Follow;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    /**
     * tab 过滤：
     * - hot      热门（默认，按投票数/关注数排序）
     * - latest   最新（按创建时间排序）
     * - following 我关注的文章（需登录）
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'hot');

        $query = Article::query()->whereNull('delete_time');

        if ($tab === 'following') {
            /** @var \App\Models\User|null $user */
            $user = $request->user();
            if (! $user) {
                return response()->json([]);
            }
            $articleIds = DB::table('follow')
                ->where('user_id', $user->user_id)
                ->where('followable_type', 'article')
                ->pluck('followable_id');
            $query->whereIn('article_id', $articleIds);
        }

        if ($tab === 'latest') {
            $query->orderByDesc('create_time');
        } else {
            $query->orderByDesc('vote_count')
                ->orderByDesc('follower_count');
        }

        $query->orderByDesc('article_id');

        // 带 page 参数时分页返回，否则保持完整数组（首页等旧调用不受影响）
        [$result, $collection] = $this->paginateOrGet($query, $request);

        $this->appendFollowStatus($collection, $request, 'article', 'article_id');
        $this->appendAuthors($collection, $request, 'user_id');
        $this->attachContentTopicsToMany($collection, 'article');

        if ($result !== $collection) {
            $result->setCollection($collection);
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:80',
            'content_markdown' => 'required|string',
            'content_rendered' => 'required|string',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'integer',
        ]);

        $article = Article::create([
            'user_id' => $request->user()->user_id,
            'title' => $validated['title'],
            'content_markdown' => $validated['content_markdown'],
            'content_rendered' => $validated['content_rendered'],
        ]);

        $this->syncContentTopics($article, 'article', $validated['topic_ids'] ?? []);

        // 发布新文章：通知粉丝（follow_user_update）与话题关注者（follow_topic_update）
        $this->notifyContentPublished($article, 'article', $validated['topic_ids'] ?? []);

        $this->appendFollowStatusToModel($article, $request, 'article');
        $this->appendAuthors(collect([$article]), $request, 'user_id');
        $this->attachContentTopics($article, 'article');

        return response()->json($article, 201);
    }

    public function show(Request $request, Article $article)
    {
        if ($article->delete_time) {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        $this->appendFollowStatusToModel($article, $request, 'article');
        $this->appendVoteStatusToModel($article, $request, 'article');
        $this->appendAuthors(collect([$article]), $request, 'user_id');
        $this->attachContentTopics($article, 'article');

        return response()->json($article);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:80',
            'content_markdown' => 'sometimes|required|string',
            'content_rendered' => 'sometimes|required|string',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'integer',
        ]);

        $article->fill($request->only(['title', 'content_markdown', 'content_rendered']));
        $article->save();

        // 只有显式传入 topic_ids 时才更新话题关联（空数组表示清空）
        if ($request->has('topic_ids')) {
            $this->syncContentTopics($article, 'article', $validated['topic_ids']);
        }

        $this->appendAuthors(collect([$article]), $request, 'user_id');
        $this->attachContentTopics($article, 'article');

        return response()->json($article);
    }

    public function destroy(Request $request, Article $article)
    {
        $user = $request->user();
        if ($user && $user->user_id !== $article->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $article->delete();

        // 文章被删除时通知文章作者（article_delete；作者本人删除时自动跳过）
        NotificationService::send(
            (int) $article->user_id,
            $user ? (int) $user->user_id : 0,
            NotificationService::TYPE_ARTICLE_DELETE,
            ['article_id' => (int) $article->article_id]
        );

        // 清理话题关联并重算统计
        $this->syncContentTopics($article, 'article', []);

        return response()->json(null, 204);
    }

    /**
     * 发布新文章后的关注推送：
     * - 通知作者粉丝（follow_user_update）
     * - 通知所选话题的关注者（follow_topic_update）
     *
     * @param string $type     内容类型（article）
     * @param array  $topicIds 关联话题 id 列表
     */
    private function notifyContentPublished(Article $article, string $type, array $topicIds): void
    {
        $senderId = (int) $article->user_id;
        $articleId = (int) $article->article_id;

        // 作者粉丝
        $userFollowers = Follow::query()
            ->where('followable_type', 'user')
            ->where('followable_id', $senderId)
            ->pluck('user_id');
        NotificationService::sendToMany($userFollowers, $senderId, NotificationService::TYPE_FOLLOW_USER_UPDATE, [
            'article_id' => $articleId,
        ]);

        // 话题关注者
        if ($topicIds) {
            $topicFollowers = Follow::query()
                ->where('followable_type', 'topic')
                ->whereIn('followable_id', array_map('intval', $topicIds))
                ->pluck('user_id');
            NotificationService::sendToMany($topicFollowers, $senderId, NotificationService::TYPE_FOLLOW_TOPIC_UPDATE, [
                'article_id' => $articleId,
            ]);
        }
    }
}
