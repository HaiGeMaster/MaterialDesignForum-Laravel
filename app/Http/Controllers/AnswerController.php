<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Follow;
use App\Models\Question;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    /**
     * 获取回答列表。
     *
     * GET /api/answers
     *
     * 支持按问题过滤：GET /api/answers?question_id=1
     * 按创建时间升序返回，附带作者信息。
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Answer::query()->whereNull('delete_time');

        if ($request->has('question_id')) {
            $query->where('question_id', $request->integer('question_id'));
        }

        // 排序：-vote_count 最多点赞、+create_time 最早优先、-create_time 最新优先（默认）
        match ($request->input('order')) {
            '-vote_count' => $query->orderByDesc('vote_count')->orderBy('answer_id'),
            '+create_time' => $query->orderBy('create_time')->orderBy('answer_id'),
            default => $query->orderByDesc('create_time')->orderByDesc('answer_id'),
        };

        [$result, $collection] = $this->paginateOrGet($query, $request);

        $this->appendAuthors($collection, $request, 'user_id');
        $this->appendVoteStatus($collection, $request, 'answer', 'answer_id');
        $this->appendAnswerCommentCounts($collection);

        return response()->json($result);
    }

    /**
     * 创建新的回答。
     *
     * POST /api/answers
     *
     * @return \Illuminate\Http\JsonResponse 创建成功的回答记录，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|integer|exists:question,question_id',
            'content_markdown' => 'required|string',
            'content_rendered' => 'required|string',
        ]);

        $answer = Answer::create([
            'question_id' => $validated['question_id'],
            'user_id' => $request->user()->user_id,
            'content_markdown' => $validated['content_markdown'],
            'content_rendered' => $validated['content_rendered'],
        ]);

        // 发送回答通知（question_answer + 关注推送）
        $this->notifyAnswerCreated($answer);

        $this->appendAuthors(collect([$answer]), $request, 'user_id');

        return response()->json($answer, 201);
    }

    /**
     * 查看单条回答详情。
     *
     * GET /api/answers/{answer}
     *
     * @param Answer $answer 路由模型绑定，根据路由中的 {answer} 自动查询出对应记录，不存在时返回 404
     * @return \Illuminate\Http\JsonResponse 指定 ID 的回答记录
     */
    public function show(Request $request, Answer $answer)
    {
        if ($answer->delete_time) {
            return response()->json(['message' => 'Answer not found.'], 404);
        }

        $this->appendAuthors(collect([$answer]), $request, 'user_id');
        $this->appendVoteStatusToModel($answer, $request, 'answer');
        $this->appendAnswerCommentCounts(collect([$answer]));

        return response()->json($answer);
    }

    /**
     * 更新指定回答的内容。
     *
     * PUT/PATCH /api/answers/{answer}
     *
     * @return \Illuminate\Http\JsonResponse 更新后的完整回答记录
     */
    public function update(Request $request, Answer $answer)
    {
        $request->validate([
            'content_markdown' => 'sometimes|required|string',
            'content_rendered' => 'sometimes|required|string',
        ]);

        $answer->fill($request->only(['content_markdown', 'content_rendered']));
        $answer->save();

        $this->appendAuthors(collect([$answer]), $request, 'user_id');

        return response()->json($answer);
    }

    /**
     * 删除指定回答。
     *
     * DELETE /api/answers/{answer}
     *
     * @return \Illuminate\Http\JsonResponse 空响应体，状态码 204（No Content）
     */
    public function destroy(Request $request, Answer $answer)
    {
        $user = $request->user();
        if ($user && $user->user_id !== $answer->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $answer->delete();

        // 回答被删除时通知回答作者（answer_delete；作者本人删除时自动跳过）
        NotificationService::send(
            (int) $answer->user_id,
            $user ? (int) $user->user_id : 0,
            NotificationService::TYPE_ANSWER_DELETE,
            [
                'answer_id' => (int) $answer->answer_id,
                'question_id' => (int) $answer->question_id,
            ]
        );

        return response()->json(null, 204);
    }

    /**
     * 回答成功后发送通知：
     * - 通知提问作者（question_answer）
     * - 关注推送：提问关注者（follow_question_update）、回答作者的粉丝（follow_user_update）
     */
    private function notifyAnswerCreated(Answer $answer): void
    {
        $senderId = (int) $answer->user_id;
        $questionId = (int) $answer->question_id;

        // 通知提问作者
        $questionOwnerId = (int) Question::query()
            ->whereKey($questionId)
            ->value('user_id');

        if ($questionOwnerId > 0) {
            $summary = mb_substr(strip_tags((string) $answer->content_rendered), 0, 200);
            NotificationService::send($questionOwnerId, $senderId, NotificationService::TYPE_QUESTION_ANSWER, [
                'question_id' => $questionId,
                'answer_id' => (int) $answer->answer_id,
                'content_rendered' => $summary,
            ]);
        }

        // 提问关注者
        $questionFollowers = Follow::query()
            ->where('followable_type', 'question')
            ->where('followable_id', $questionId)
            ->pluck('user_id');
        NotificationService::sendToMany($questionFollowers, $senderId, NotificationService::TYPE_FOLLOW_QUESTION_UPDATE, [
            'question_id' => $questionId,
        ]);

        // 回答作者的粉丝
        $userFollowers = Follow::query()
            ->where('followable_type', 'user')
            ->where('followable_id', $senderId)
            ->pluck('user_id');
        NotificationService::sendToMany($userFollowers, $senderId, NotificationService::TYPE_FOLLOW_USER_UPDATE, [
            'question_id' => $questionId,
            'answer_id' => (int) $answer->answer_id,
        ]);
    }

    /**
     * 批量附加回答的评论数量（comment_count），用于评论按钮徽标。
     */
    private function appendAnswerCommentCounts($items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $counts = \Illuminate\Support\Facades\DB::table('comment')
            ->where('commentable_type', 'answer')
            ->whereNull('delete_time')
            ->whereIn('commentable_id', $items->pluck('answer_id'))
            ->selectRaw('commentable_id, COUNT(*) as cnt')
            ->groupBy('commentable_id')
            ->pluck('cnt', 'commentable_id');

        $items->each(function ($answer) use ($counts) {
            $answer->comment_count = (int) ($counts[$answer->answer_id] ?? 0);
        });
    }
}
