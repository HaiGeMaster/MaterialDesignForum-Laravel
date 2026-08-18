<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Follow;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    /**
     * 给集合中的每条记录附加当前登录用户对它的投票状态（my_vote）。
     *
     * my_vote 取值：'up' / 'down' / null（未投票）。
     * 内容表自带 vote_up_count / vote_down_count / vote_count 字段，
     * Eloquent 序列化时自动返回，因此这里只附加用户投票状态。
     *
     * @param  \Illuminate\Support\Collection  $items    目标模型集合
     * @param  string  $type      投票目标类型：question / answer / article / comment / reply
     * @param  string  $idColumn  目标主键列名，如 question_id
     */
    protected function appendVoteStatus(
        Collection $items,
        Request $request,
        string $type,
        string $idColumn
    ): Collection {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user || $items->isEmpty()) {
            return $items->each(fn ($item) => $item->my_vote = null);
        }

        $votes = DB::table('vote')
            ->where('user_id', $user->user_id)
            ->where('votable_type', $type)
            ->whereIn('votable_id', $items->pluck($idColumn))
            ->get();

        $byId = [];
        foreach ($votes as $vote) {
            $byId[$vote->votable_id] = $vote->type;
        }

        return $items->each(function ($item) use ($byId, $idColumn) {
            $item->my_vote = $byId[$item->getAttribute($idColumn)] ?? null;
        });
    }

    /**
     * 给单个模型附加当前登录用户对它的投票状态（my_vote）。
     *
     * @param  \Illuminate\Database\Eloquent\Model  $item
     */
    protected function appendVoteStatusToModel($item, Request $request, string $type)
    {
        $item->my_vote = null;

        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if ($user && $item->getKey()) {
            $item->my_vote = DB::table('vote')
                ->where('user_id', $user->user_id)
                ->where('votable_type', $type)
                ->where('votable_id', $item->getKey())
                ->value('type');
        }

        return $item;
    }

    /**
     * 给集合中的每条记录附加当前登录用户对它的关注状态（is_following）。
     *
     * 关注状态直接随列表数据返回，前端无需再单独请求关注状态接口。
     * 未登录用户一律返回 false，便于游客浏览。
     *
     * @param  \Illuminate\Support\Collection  $items    目标模型集合
     * @param  string  $type      关注目标类型：user / topic / question / article
     * @param  string  $idColumn  目标主键列名，如 topic_id
     */
    protected function appendFollowStatus(
        Collection $items,
        Request $request,
        string $type,
        string $idColumn
    ): Collection {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user || $items->isEmpty()) {
            return $items->each(fn ($item) => $item->is_following = false);
        }

        $followedIds = Follow::query()
            ->where('user_id', $user->user_id)
            ->where('followable_type', $type)
            ->whereIn('followable_id', $items->pluck($idColumn))
            ->pluck('followable_id')
            ->all();

        $followedSet = array_flip($followedIds);

        return $items->each(function ($item) use ($followedSet) {
            $item->is_following = isset($followedSet[$item->getKey()]);
        });
    }

    /**
     * 给单个模型附加当前登录用户对它的关注状态（is_following）。
     *
     * @param  \Illuminate\Database\Eloquent\Model  $item
     */
    protected function appendFollowStatusToModel($item, Request $request, string $type)
    {
        $item->is_following = false;

        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if ($user && $item->getKey()) {
            $item->is_following = Follow::query()
                ->where('user_id', $user->user_id)
                ->where('followable_type', $type)
                ->where('followable_id', $item->getKey())
                ->exists();
        }

        return $item;
    }

    /**
     * 给集合中的每条记录附带其作者（creator）信息。
     *
     * 批量按 user_id 查一次用户，避免 N+1 查询。
     * 作者信息附带关注状态（is_following）与计数，便于前端直接展示。
     *
     * @param  \Illuminate\Support\Collection  $items     目标模型集合（topic / question / article）
     * @param  string  $userIdColumn  目标表中作者外键列名，如 user_id
     * @param  string  $userKeyColumn 用户表主键列名，如 user_id
     */
    protected function appendAuthors(Collection $items, Request $request, string $userIdColumn, string $userKeyColumn = 'user_id')
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $authorIds = $items->pluck($userIdColumn)
            ->filter()
            ->unique()
            ->all();

        if (empty($authorIds)) {
            return $items->each(fn ($item) => $item->creator = null);
        }

        $authors = User::query()
            ->whereIn($userKeyColumn, $authorIds)
            ->get()
            ->keyBy($userKeyColumn);

        $followedIds = [];
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if ($user) {
            $followedIds = Follow::query()
                ->where('user_id', $user->user_id)
                ->where('followable_type', 'user')
                ->whereIn('followable_id', $authorIds)
                ->pluck('followable_id')
                ->all();
        }
        $followedSet = array_flip($followedIds);

        $this->attachUserCounts($authors->values());

        return $items->each(function ($item) use ($authors, $followedSet) {
            $author = $authors->get($item->getAttribute('user_id'));
            if ($author) {
                $author->is_following = isset($followedSet[$author->getKey()]);
            }
            $item->creator = $author ?: null;
        });
    }

    /**
     * 同步「内容（问题/文章）↔ 话题」的关联关系（topicable 多态表），
     * 并重算 topics 表中的 article_count / question_count 冗余统计。
     *
     * 传入空数组或 null 会清空该内容的所有话题关联。
     *
     * @param  \Illuminate\Database\Eloquent\Model  $item      问题或文章模型
     * @param  string  $type      内容类型：question / article
     * @param  int[]|null  $topicIds  要关联的话题 id 列表
     */
    protected function syncContentTopics($item, string $type, ?array $topicIds): void
    {
        $contentId = $item->getKey();
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $topicIds))));

        DB::transaction(function () use ($type, $contentId, $ids) {
            // 清空旧关联
            DB::table('topicable')
                ->where('topicable_type', $type)
                ->where('topicable_id', $contentId)
                ->delete();

            // 写入新关联（跳过不存在或已删除的话题）
            if ($ids) {
                $valid = Topic::query()
                    ->whereIn('topic_id', $ids)
                    ->whereNull('delete_time')
                    ->pluck('topic_id')
                    ->all();

                foreach ($valid as $topicId) {
                    DB::table('topicable')->insert([
                        'topic_id' => $topicId,
                        'topicable_id' => $contentId,
                        'topicable_type' => $type,
                        'create_time' => now(),
                    ]);
                }
            }

            $this->recountTopicStats($type);
        });
    }

    /**
     * 重算 topics 表的 article_count / question_count。
     *
     * @param  string|null  $type  仅重算指定类型（question / article），null 则全部
     */
    protected function recountTopicStats(?string $type = null): void
    {
        $types = $type ? [$type] : ['article', 'question'];
        $column = ['article' => 'article_count', 'question' => 'question_count'];

        foreach ($types as $t) {
            $counts = DB::table('topicable')
                ->where('topicable_type', $t)
                ->selectRaw('topic_id, COUNT(*) as cnt')
                ->groupBy('topic_id')
                ->pluck('cnt', 'topic_id');

            foreach (Topic::query()->pluck('topic_id') as $topicId) {
                $new = (int) ($counts[$topicId] ?? 0);
                if (Topic::query()->where('topic_id', $topicId)->value($column[$t]) !== $new) {
                    Topic::query()->where('topic_id', $topicId)->update([$column[$t] => $new]);
                }
            }
        }
    }

    /**
     * 给单个内容模型附带其话题列表（topics）。
     *
     * @param  \Illuminate\Database\Eloquent\Model  $item
     */
    protected function attachContentTopics($item, string $type)
    {
        $ids = DB::table('topicable')
            ->where('topicable_type', $type)
            ->where('topicable_id', $item->getKey())
            ->orderBy('topic_id')
            ->pluck('topic_id');

        $item->topics = $ids->isEmpty()
            ? []
            : Topic::query()->whereIn('topic_id', $ids)->orderBy('topic_id')->get()->all();

        return $item;
    }

    /**
     * 给集合中的每个内容模型附带话题列表（topics），批量查询避免 N+1。
     *
     * @param  \Illuminate\Support\Collection  $items
     */
    protected function attachContentTopicsToMany(Collection $items, string $type): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $idColumn = $type === 'article' ? 'article_id' : 'question_id';
        $contentIds = $items->pluck($idColumn)->all();

        $relations = DB::table('topicable')
            ->where('topicable_type', $type)
            ->whereIn('topicable_id', $contentIds)
            ->get();

        $topicIds = $relations->pluck('topic_id')->unique()->values();
        $topics = $topicIds->isEmpty()
            ? collect()
            : Topic::query()->whereIn('topic_id', $topicIds)->orderBy('topic_id')->get()->keyBy('topic_id');

        $byContent = $relations->groupBy('topicable_id');

        return $items->each(function ($item) use ($byContent, $topics, $idColumn) {
            $ids = collect($byContent->get($item->getAttribute($idColumn), []))
                ->pluck('topic_id')
                ->unique();
            $item->topics = $ids
                ->map(fn ($id) => $topics->get($id))
                ->filter()
                ->values()
                ->all();
        });
    }

    /**
     * 给用户集合实时计算并覆盖关注量 / 粉丝量 / 回答数量。
     *
     * user 表中的 followee_count / follower_count / answer_count 是冗余字段，
     * 但项目里没有任何写入逻辑维护它们（FollowController 只写 follow 表），
     * 导致始终为 0。这里改为返回时按 follow / answer 表实时统计，避免数据漂移。
     *
     * @param  \Illuminate\Support\Collection  $users  用户模型集合
     */
    protected function attachUserCounts(Collection $users)
    {
        if ($users->isEmpty()) {
            return $users;
        }

        $ids = $users->pluck('user_id')->all();

        // 关注量：该用户关注了多少人
        $followee = Follow::query()
            ->where('followable_type', 'user')
            ->whereIn('user_id', $ids)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        // 粉丝量：有多少人关注了该用户
        $follower = Follow::query()
            ->where('followable_type', 'user')
            ->whereIn('followable_id', $ids)
            ->selectRaw('followable_id, COUNT(*) as cnt')
            ->groupBy('followable_id')
            ->pluck('cnt', 'followable_id');

        // 回答数量
        $answer = Answer::query()
            ->whereIn('user_id', $ids)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        return $users->each(function ($user) use ($followee, $follower, $answer) {
            $id = $user->getKey();
            $user->followee_count = (int) ($followee[$id] ?? 0);
            $user->follower_count = (int) ($follower[$id] ?? 0);
            $user->answer_count = (int) ($answer[$id] ?? 0);
        });
    }

    /**
     * 列表接口的可选分页：请求带 page 参数时返回 Laravel 分页结构
     * （{ data, current_page, last_page, ... }），不带 page 时返回完整数组，
     * 保证首页 / 轮播等现有调用不受影响。
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return array{0: \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    protected function paginateOrGet($query, Request $request): array
    {
        if ($request->has('page')) {
            $paginator = $query->paginate($request->integer('per_page', 15));
            return [$paginator, $paginator->getCollection()];
        }

        $items = $query->get();
        return [$items, $items];
    }
}
