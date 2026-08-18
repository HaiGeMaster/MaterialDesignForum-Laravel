<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    /** 关注目标类型 → 目标表名 */
    private const TYPE_TABLE = [
        'user' => 'user',
        'topic' => 'topic',
        'question' => 'question',
        'article' => 'article',
    ];

    /** 关注目标类型 → 目标表主键 */
    private const TYPE_PRIMARY_KEY = [
        'user' => 'user_id',
        'topic' => 'topic_id',
        'question' => 'question_id',
        'article' => 'article_id',
    ];

    public function index()
    {
        return response()->json(Follow::query()->orderBy('follow_id')->get());
    }

    public function store(Request $request)
    {
        $follow = Follow::create($request->all());
        return response()->json($follow, 201);
    }

    public function show(Follow $follow)
    {
        return response()->json($follow);
    }

    public function update(Request $request, Follow $follow)
    {
        $follow->fill($request->all());
        $follow->save();
        return response()->json($follow);
    }

    public function destroy(Follow $follow)
    {
        $follow->delete();
        return response()->json(null, 204);
    }

    /**
     * 获取关注关系下的用户列表（用户浏览对话框）
     *
     * POST /follows/get
     * body: { modes, followable_type, followable_id, page, per_page }
     *
     * modes:
     * - followers：关注了某对象（user / topic / question / article）的所有用户
     * - followees：某用户关注的所有用户（仅 followable_type=user 有意义）
     *
     * 返回 Laravel 分页结构，每条用户附带实时统计（followee_count /
     * follower_count / answer_count）与当前登录用户的关注状态（is_following）。
     */
    public function get(Request $request)
    {
        $data = $request->validate([
            'modes' => ['required', 'string', 'in:followers,followees'],
            'followable_type' => ['required', 'string', 'in:user,topic,question,article'],
            'followable_id' => ['required', 'integer'],
        ]);

        $modes = $data['modes'];
        $type = $data['followable_type'];
        $id = (int) $data['followable_id'];

        $query = User::query();

        if ($modes === 'followers') {
            // 关注了该对象的用户列表
            $ids = Follow::query()
                ->where('followable_type', $type)
                ->where('followable_id', $id)
                ->pluck('user_id');
            $query->whereIn('user_id', $ids);
        } else {
            // 该用户关注的用户列表
            $ids = Follow::query()
                ->where('user_id', $id)
                ->where('followable_type', 'user')
                ->pluck('followable_id');
            $query->whereIn('user_id', $ids);
        }

        $query->orderByDesc('user_id');

        [$result, $collection] = $this->paginateOrGet($query, $request);

        $this->attachUserCounts($collection);
        $this->appendFollowStatus($collection, $request, 'user', 'user_id');

        if ($result !== $collection) {
            $result->setCollection($collection);
        }

        return response()->json($result);
    }

    /**
     * 切换关注状态（关注 / 取消关注）
     *
     * 支持关注目标类型：user 用户、topic 话题、question 提问、article 文章。
     * 需登录。校验目标存在性，幂等操作：已关注则取消，未关注则创建。
     * 返回最新关注状态与关注人数。
     */
    public function toggle(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:user,topic,question,article'],
            'id' => ['required', 'integer'],
        ]);

        $type = $data['type'];
        $id = (int) $data['id'];

        // 校验关注目标存在
        $exists = DB::table(self::TYPE_TABLE[$type])
            ->where(self::TYPE_PRIMARY_KEY[$type], $id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'id' => '关注的目标不存在。',
            ]);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        $follow = Follow::query()
            ->where('user_id', $user->user_id)
            ->where('followable_type', $type)
            ->where('followable_id', $id)
            ->first();

        if ($follow) {
            $follow->delete();
            $following = false;
        } else {
            Follow::create([
                'user_id' => $user->user_id,
                'followable_type' => $type,
                'followable_id' => $id,
            ]);
            $following = true;

            // 发送关注通知（user_follow / topic_follow / question_follow / article_follow）
            $this->notifyFollow($type, $id, $user);
        }

        return response()->json([
            'following' => $following,
            'follower_count' => $this->followerCount($type, $id),
        ]);
    }

    /**
     * 关注成功后发送通知：
     * - user 关注用户    → 通知被关注者 user_follow
     * - topic 关注话题   → 通知话题作者 topic_follow
     * - question 关注提问 → 通知提问作者 question_follow
     * - article 关注文章 → 通知文章作者 article_follow
     */
    private function notifyFollow(string $type, int $id, User $sender): void
    {
        $ownerId = 0;
        $context = [];

        if ($type === 'user') {
            $ownerId = $id;
        } else {
            $ownerId = (int) DB::table(self::TYPE_TABLE[$type])
                ->where(self::TYPE_PRIMARY_KEY[$type], $id)
                ->value('user_id');
            $context[self::TYPE_PRIMARY_KEY[$type]] = $id;
        }

        $notifyType = match ($type) {
            'user' => NotificationService::TYPE_USER_FOLLOW,
            'topic' => NotificationService::TYPE_TOPIC_FOLLOW,
            'question' => NotificationService::TYPE_QUESTION_FOLLOW,
            'article' => NotificationService::TYPE_ARTICLE_FOLLOW,
            default => null,
        };

        if ($notifyType) {
            NotificationService::send($ownerId, $sender->user_id, $notifyType, $context);
        }
    }

    /**
     * 批量查询当前用户对若干目标的关注状态
     *
     * GET /follows/status?type=user&ids=1,2,3
     * 未登录时全部返回 false，便于游客浏览。
     */
    public function status(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:user,topic,question,article'],
            'ids' => ['required', 'string'],
        ]);

        $ids = array_values(array_unique(array_map('intval', explode(',', $data['ids']))));
        $ids = array_filter($ids, fn ($id) => $id > 0);

        // 初始全部为未关注
        $result = [];
        foreach ($ids as $id) {
            $result[(string) $id] = false;
        }

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if ($user && $ids) {
            $followedIds = Follow::query()
                ->where('user_id', $user->user_id)
                ->where('followable_type', $data['type'])
                ->whereIn('followable_id', $ids)
                ->pluck('followable_id')
                ->all();

            foreach ($followedIds as $followedId) {
                $result[(string) $followedId] = true;
            }
        }

        return response()->json($result);
    }

    /**
     * 统计某目标的关注人数
     */
    private function followerCount(string $type, int $id): int
    {
        return Follow::query()
            ->where('followable_type', $type)
            ->where('followable_id', $id)
            ->count();
    }
}
