<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TopicController extends Controller
{
    public function index(Request $request)
    {
        $query = Topic::query()->whereNull('delete_time');

        // 我关注的话题
        if ($request->query('tab') === 'following') {
            /** @var \App\Models\User|null $user */
            $user = $request->user();
            if (! $user) {
                return response()->json([]);
            }
            $topicIds = DB::table('follow')
                ->where('user_id', $user->user_id)
                ->where('followable_type', 'topic')
                ->pluck('followable_id');
            $query->whereIn('topic_id', $topicIds);
        }

        // 推荐话题：按关注人数排序（带 page 参数时分页返回）
        [$result, $collection] = $this->paginateOrGet(
            $query->orderByDesc('follower_count')->orderByDesc('topic_id'),
            $request
        );

        $this->appendFollowStatus($collection, $request, 'topic', 'topic_id');
        $this->appendAuthors($collection, $request, 'user_id');

        if ($result !== $collection) {
            $result->setCollection($collection);
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        if (! $request->user()->hasAbility('ability_create_topic')) {
            return response()->json(['message' => __('messages.http_topic_1')], 403);
        }

        $topic = Topic::create($request->all());

        // 同步作者的话题计数（user.topic_count）
        $this->adjustUserContentCount('topic', $topic->user_id);

        return response()->json($topic, 201);
    }

    public function show(Request $request, Topic $topic)
    {
        // 通过 topicable 多态关联表取出该话题下的文章与问题
        $related = DB::table('topicable')
            ->where('topic_id', $topic->topic_id)
            ->get();

        $articleIds = $related->where('topicable_type', 'article')->pluck('topicable_id');
        $questionIds = $related->where('topicable_type', 'question')->pluck('topicable_id');

        // 话题创建者
        $creator = $topic->user_id
            ? User::query()->where('user_id', $topic->user_id)->first()
            : null;

        // 附加当前登录用户对话题及其创建者的关注状态
        $this->appendFollowStatusToModel($topic, $request, 'topic');
        if ($creator) {
            $this->appendFollowStatusToModel($creator, $request, 'user');
        }

        $articles = Article::query()
            ->when($articleIds->isNotEmpty(), fn ($q) => $q->whereIn('article_id', $articleIds))
            ->orderByDesc('article_id')
            ->get();
        $questions = Question::query()
            ->when($questionIds->isNotEmpty(), fn ($q) => $q->whereIn('question_id', $questionIds))
            ->orderByDesc('question_id')
            ->get();

        $this->appendAuthors($articles, $request, 'user_id');
        $this->appendAuthors($questions, $request, 'user_id');

        return response()->json([
            'topic' => $topic,
            'creator' => $creator,
            'articles' => $articles,
            'questions' => $questions,
        ]);
    }

    public function update(Request $request, Topic $topic)
    {
        $oldUserId = $topic->user_id;

        $topic->fill($request->all());
        $topic->save();

        // 创建者被改动时，计数从旧作者转移到新作者
        if ($topic->wasChanged('user_id')) {
            $this->adjustUserContentCount('topic', $oldUserId, -1);
            $this->adjustUserContentCount('topic', $topic->user_id);
        }

        return response()->json($topic);
    }

    public function destroy(Request $request, Topic $topic)
    {
        $user = $request->user();

        // 已被后台软删除过的话题不再重复扣减
        $wasDeleted = $topic->delete_time !== null;

        $topic->delete();

        if (! $wasDeleted) {
            $this->adjustUserContentCount('topic', $topic->user_id, -1);
        }

        // 话题被删除时通知话题创建者（topic_delete；创建者本人删除时自动跳过）
        NotificationService::send(
            (int) $topic->user_id,
            $user ? (int) $user->user_id : 0,
            NotificationService::TYPE_TOPIC_DELETE,
            ['topic_id' => (int) $topic->topic_id]
        );

        return response()->json(null, 204);
    }
}
