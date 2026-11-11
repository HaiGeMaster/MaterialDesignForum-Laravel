<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Follow;
use App\Models\Question;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    /**
     * tab 过滤：
     * - hot      热门（默认，按投票数/回答数排序）
     * - latest   最新（按创建时间排序）
     * - following 我关注的问题（需登录）
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'hot');

        $query = Question::query()->whereNull('delete_time');

        if ($tab === 'following') {
            /** @var \App\Models\User|null $user */
            $user = $request->user();
            if (! $user) {
                return response()->json([]);
            }
            $questionIds = DB::table('follow')
                ->where('user_id', $user->user_id)
                ->where('followable_type', 'question')
                ->pluck('followable_id');
            $query->whereIn('question_id', $questionIds);
        }

        if ($tab === 'latest') {
            $query->orderByDesc('create_time');
        } else {
            $query->orderByDesc('vote_count')
                ->orderByDesc('answer_count');
        }

        $query->orderByDesc('question_id');

        // 带 page 参数时分页返回，否则保持完整数组（首页等旧调用不受影响）
        [$result, $collection] = $this->paginateOrGet($query, $request);

        $this->appendFollowStatus($collection, $request, 'question', 'question_id');
        $this->appendAuthors($collection, $request, 'user_id');
        $this->attachContentTopicsToMany($collection, 'question');

        if ($result !== $collection) {
            $result->setCollection($collection);
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        if (! $request->user()->hasAbility('ability_create_question')) {
            return response()->json(['message' => __('messages.http_question_1')], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:80',
            'content_markdown' => 'required|string',
            'content_rendered' => 'required|string',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'integer',
        ]);

        $question = Question::create([
            'user_id' => $request->user()->user_id,
            'title' => $validated['title'],
            'content_markdown' => $validated['content_markdown'],
            'content_rendered' => $validated['content_rendered'],
        ]);

        // 同步作者的提问计数（user.question_count）
        $this->adjustUserContentCount('question', $question->user_id);

        $this->syncContentTopics($question, 'question', $validated['topic_ids'] ?? []);

        // 发布新提问：通知粉丝（follow_user_update）与话题关注者（follow_topic_update）
        $this->notifyContentPublished($question, 'question', $validated['topic_ids'] ?? []);

        $this->appendFollowStatusToModel($question, $request, 'question');
        $this->appendAuthors(collect([$question]), $request, 'user_id');
        $this->attachContentTopics($question, 'question');

        return response()->json($question, 201);
    }

    public function show(Request $request, Question $question)
    {
        if ($question->delete_time) {
            return response()->json(['message' => 'Question not found.'], 404);
        }

        $this->appendFollowStatusToModel($question, $request, 'question');
        $this->appendVoteStatusToModel($question, $request, 'question');
        $this->appendAuthors(collect([$question]), $request, 'user_id');
        $this->attachContentTopics($question, 'question');

        $answers = Answer::query()
            ->where('question_id', $question->question_id)
            ->whereNull('delete_time')
            ->orderBy('create_time')
            ->orderBy('answer_id')
            ->get();

        $this->appendAuthors($answers, $request, 'user_id');
        $this->appendVoteStatus($answers, $request, 'answer', 'answer_id');

        $data = $question->toArray();
        $data['answers'] = $answers;

        return response()->json($data);
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:80',
            'content_markdown' => 'sometimes|required|string',
            'content_rendered' => 'sometimes|required|string',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'integer',
        ]);

        $question->fill($request->only(['title', 'content_markdown', 'content_rendered']));
        $question->save();

        // 只有显式传入 topic_ids 时才更新话题关联（空数组表示清空）
        if ($request->has('topic_ids')) {
            $this->syncContentTopics($question, 'question', $validated['topic_ids']);
        }

        $this->appendAuthors(collect([$question]), $request, 'user_id');
        $this->attachContentTopics($question, 'question');

        return response()->json($question);
    }

    public function destroy(Request $request, Question $question)
    {
        $user = $request->user();
        if ($user && $user->user_id !== $question->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // 已被后台软删除过的提问不再重复扣减
        $wasDeleted = $question->delete_time !== null;

        $question->delete();

        if (! $wasDeleted) {
            $this->adjustUserContentCount('question', $question->user_id, -1);
        }

        // 提问被删除时通知提问作者（question_delete；作者本人删除时自动跳过）
        NotificationService::send(
            (int) $question->user_id,
            $user ? (int) $user->user_id : 0,
            NotificationService::TYPE_QUESTION_DELETE,
            ['question_id' => (int) $question->question_id]
        );

        // 清理话题关联并重算统计
        $this->syncContentTopics($question, 'question', []);

        return response()->json(null, 204);
    }

    /**
     * 发布新提问/文章后的关注推送：
     * - 通知作者粉丝（follow_user_update）
     * - 通知所选话题的关注者（follow_topic_update）
     *
     * @param string $type     内容类型：question / article
     * @param array  $topicIds 关联话题 id 列表
     */
    private function notifyContentPublished($model, string $type, array $topicIds): void
    {
        $senderId = (int) $model->user_id;
        $contentKey = $type === 'question' ? 'question_id' : 'article_id';
        $contentId = (int) $model->getKey();

        // 作者粉丝
        $userFollowers = Follow::query()
            ->where('followable_type', 'user')
            ->where('followable_id', $senderId)
            ->pluck('user_id');
        NotificationService::sendToMany($userFollowers, $senderId, NotificationService::TYPE_FOLLOW_USER_UPDATE, [
            $contentKey => $contentId,
        ]);

        // 话题关注者
        if ($topicIds) {
            $topicFollowers = Follow::query()
                ->where('followable_type', 'topic')
                ->whereIn('followable_id', array_map('intval', $topicIds))
                ->pluck('user_id');
            NotificationService::sendToMany($topicFollowers, $senderId, NotificationService::TYPE_FOLLOW_TOPIC_UPDATE, [
                $contentKey => $contentId,
            ]);
        }
    }
}
