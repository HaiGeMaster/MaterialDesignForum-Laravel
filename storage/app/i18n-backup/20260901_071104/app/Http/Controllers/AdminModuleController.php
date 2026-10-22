<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Reply;
use App\Models\Report;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 管理后台通用模块列表接口
 *
 * 用单个控制器 + 模块配置数组统一处理 topics / questions / answers / articles /
 * comments / replies / user-groups / users / reports 这 9 个内容模块的"数据预览"列表。
 *
 * 设计目标：仅做"管理员后台数据预览视图"——分页 + 关键字搜索 + 排序 + 关联用户名，
 * 不做单条 CRUD（后续可在每个模块独立扩展）。
 */
class AdminModuleController extends Controller
{
    /**
     * 各模块的查询配置
     *
     * 字段说明：
     *  - model:         Eloquent 模型类
     *  - search:        可参与关键字 LIKE 搜索的列（OR 连接）
     *  - sortable:      允许前端 order_by 指定的列白名单
     *  - relations:     预加载的关联（避免 N+1）
     *  - soft_delete:   是否含 delete_time 软删除列（true 时默认排除已删除行）
     *  - update_fields: 管理员可编辑的字段白名单（空数组=不可编辑）
     *  - delete_field:  软删除字段名（'delete_time' / 'disable_time'，null=硬删除）
     */
    private const MODULES = [
        'topics' => [
            'model'        => Topic::class,
            'search'       => ['name', 'description'],
            'sortable'     => ['topic_id', 'user_id', 'follower_count', 'article_count', 'question_count', 'create_time', 'update_time'],
            'relations'    => [],
            'soft_delete'  => true,
            'update_fields' => ['name', 'description'],
            'delete_field'  => 'delete_time',
        ],
        'questions' => [
            'model'        => Question::class,
            'search'       => ['title'],
            'sortable'     => ['question_id', 'user_id', 'comment_count', 'answer_count', 'follower_count', 'vote_count', 'vote_up_count', 'vote_down_count', 'create_time', 'last_answer_time', 'update_time'],
            'relations'    => [],
            'soft_delete' => true,
            'update_fields' => ['title', 'content_markdown', 'content_rendered'],
            'delete_field'  => 'delete_time',
        ],
        'answers' => [
            'model'        => Answer::class,
            'search'       => ['content_markdown'],
            'sortable'     => ['answer_id', 'question_id', 'user_id', 'comment_count', 'vote_count', 'vote_up_count', 'vote_down_count', 'create_time', 'update_time'],
            'relations'    => [],
            'soft_delete' => true,
            'update_fields' => ['content_markdown', 'content_rendered'],
            'delete_field'  => 'delete_time',
        ],
        'articles' => [
            'model'        => Article::class,
            'search'       => ['title'],
            'sortable'     => ['article_id', 'user_id', 'comment_count', 'follower_count', 'vote_count', 'vote_up_count', 'vote_down_count', 'create_time', 'update_time'],
            'relations'    => [],
            'soft_delete' => true,
            'update_fields' => ['title', 'content_markdown', 'content_rendered'],
            'delete_field'  => 'delete_time',
        ],
        'comments' => [
            'model'        => Comment::class,
            'search'       => ['content'],
            'sortable'     => ['comment_id', 'user_id', 'reply_count', 'vote_count', 'vote_up_count', 'vote_down_count', 'create_time', 'update_time'],
            'relations'    => [],
            'soft_delete' => true,
            'update_fields' => ['content'],
            'delete_field'  => 'delete_time',
        ],
        'replies' => [
            'model'        => Reply::class,
            'search'       => ['content'],
            'sortable'     => ['reply_id', 'user_id', 'reply_count', 'vote_count', 'vote_up_count', 'vote_down_count', 'create_time', 'update_time'],
            'relations'    => [],
            'soft_delete' => true,
            'update_fields' => ['content'],
            'delete_field'  => 'delete_time',
        ],
        'user-groups' => [
            'model'        => UserGroup::class,
            'search'       => ['user_group_name', 'user_group_description'],
            'sortable'     => ['user_group_id', 'user_group_user_count', 'is_admin', 'create_time', 'update_time'],
            'relations'    => [],
            'soft_delete' => true,
            'update_fields' => [
                'user_group_name', 'user_group_description',
                'user_group_icon', 'user_group_color',
                'user_group_icon_show', 'is_admin',
                'ability_normal_login', 'ability_admin_login',
                'ability_admin_manage_user_group', 'ability_admin_manage_user',
                'ability_admin_manage_topic', 'ability_admin_manage_question',
                'ability_admin_manage_article', 'ability_admin_manage_comment',
                'ability_admin_manage_answer', 'ability_admin_manage_reply',
                'ability_admin_manage_report', 'ability_admin_manage_option',
                'ability_create_article', 'ability_create_question',
                'ability_create_answer', 'ability_create_comment',
                'ability_create_reply', 'ability_create_topic',
                'ability_edit_own_article', 'ability_edit_own_question',
                'ability_edit_own_answer', 'ability_edit_own_comment',
                'ability_edit_own_reply', 'ability_edit_own_topic',
                'ability_delete_own_article', 'ability_delete_own_question',
                'ability_delete_own_answer', 'ability_delete_own_comment',
                'ability_delete_own_reply', 'ability_delete_own_topic',
                'time_before_edit_article', 'time_before_edit_question',
                'time_before_edit_answer', 'time_before_edit_comment',
                'time_before_edit_reply', 'time_before_edit_topic',
                'time_before_delete_article', 'time_before_delete_question',
                'time_before_delete_answer', 'time_before_delete_comment',
                'time_before_delete_reply', 'time_before_delete_topic',
                'ability_edit_article_only_no_comment', 'ability_edit_question_only_no_answer',
                'ability_edit_answer_only_no_comment', 'ability_edit_question_only_no_comment',
                'ability_edit_comment_only_no_reply', 'ability_edit_reply_only_no_reply',
                'ability_edit_topic_only_no_article_or_question',
                'ability_delete_article_only_no_comment', 'ability_delete_question_only_no_answer',
                'ability_delete_answer_only_no_comment', 'ability_delete_question_only_no_comment',
                'ability_delete_comment_only_no_reply', 'ability_delete_reply_only_no_reply',
                'ability_delete_topic_only_no_article_or_question',
                'ability_edit_own_info', 'ability_vote',
            ],
            'delete_field'  => 'delete_time',
        ],
        'users' => [
            'model'        => User::class,
            'search'       => ['username', 'email'],
            'sortable'     => ['user_id', 'username', 'follower_count', 'followee_count', 'following_topic_count', 'following_article_count', 'following_question_count', 'article_count', 'question_count', 'answer_count', 'comment_count', 'reply_count', 'notification_unread', 'create_time', 'last_login_time', 'update_time', 'disable_time'],
            'relations'    => ['userGroup'],
            'soft_delete' => false,
            'update_fields' => ['username', 'email', 'user_group_id', 'headline', 'bio', 'blog', 'company', 'location', 'language'],
            'delete_field'  => 'disable_time',
        ],
        'reports' => [
            'model'        => Report::class,
            'search'       => ['reason'],
            'sortable'     => ['report_id', 'user_id', 'report_handle_state', 'create_time'],
            'relations'    => [],
            'soft_delete' => true,
            'update_fields' => ['report_handle_state'],
            'delete_field'  => null,
        ],
    ];

    /**
     * GET /admin/modules/{module}
     *
     * Query 参数：
     *  - page:       页码（带 page 走 Laravel 分页）
     *  - per_page:   每页条数（默认 15，上限 100）
     *  - search:     关键字（对模块配置的 search 列做 LIKE OR 查询）
     *  - order_by:   排序列（必须在模块 sortable 白名单内，默认主键）
     *  - order_dir:  asc / desc（默认 desc）
     */
    public function index(string $module, Request $request)
    {
        $config = self::MODULES[$module] ?? null;
        if (! $config) {
            return response()->json(['message' => 'Unknown module'], 404);
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $page    = $request->integer('page', 1);
        $search  = trim((string) $request->query('search', ''));
        $orderBy = $request->query('order_by');
        $orderDir = strtolower((string) $request->query('order_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! $orderBy || ! in_array($orderBy, $config['sortable'], true)) {
            $orderBy = $config['sortable'][0];
        }

        /** @var \Illuminate\Database\Eloquent\Model $modelClass */
        $modelClass = $config['model'];

        $query = $modelClass::query();

        // 软删除过滤：除 user 表外（user 用 disable_time 表示禁用），其他表用 delete_time
        if ($config['soft_delete']) {
            $query->whereNull('delete_time');
        }

        // 关键字搜索：多列 OR LIKE
        if ($search !== '' && ! empty($config['search'])) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($config, $escaped) {
                foreach ($config['search'] as $column) {
                    $q->orWhere($column, 'like', "%{$escaped}%");
                }
            });
        }

        // 预加载关联
        if (! empty($config['relations'])) {
            $query->with($config['relations']);
        }

        $query->orderBy($orderBy, $orderDir);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $collection = $paginator->getCollection();

        // 批量预取 username（避免 N+1）：收集本页出现的所有 user_id，一次查回
        $usernames = [];
        if ($module !== 'user-groups') {
            $userIds = $collection->pluck('user_id')->filter()->unique()->values()->all();
            if (! empty($userIds)) {
                $usernames = User::query()
                    ->whereIn('user_id', $userIds)
                    ->pluck('username', 'user_id');
            }
        }

        // replies 模块额外预取“回复目标用户”的用户名（replyable_user_id）
        $replyableUsernames = [];
        if ($module === 'replies') {
            $replyableUserIds = $collection->pluck('replyable_user_id')->filter()->unique()->values()->all();
            if (! empty($replyableUserIds)) {
                $replyableUsernames = User::query()
                    ->whereIn('user_id', $replyableUserIds)
                    ->pluck('username', 'user_id');
            }
        }

        $items = $collection->map(function ($item) use ($module, $usernames) {
            $array = $item->toArray();

            // 把预取到的 username 附到每行
            if ($module !== 'user-groups' && ! empty($array['user_id'])) {
                $array['username'] = $usernames[$array['user_id']] ?? null;
            }

            // replies 模块附上回复目标用户的用户名
            if ($module === 'replies' && ! empty($array['replyable_user_id'])) {
                $array['replyable_username'] = $replyableUsernames[$array['replyable_user_id']] ?? null;
            }

            // users 模块把关联用户组名扁平化
            if ($module === 'users' && isset($array['user_group']['user_group_name'])) {
                $array['user_group_name'] = $array['user_group']['user_group_name'];
            }

            return $array;
        });

        return response()->json([
            'data'          => $items,
            'total'         => $paginator->total(),
            'current_page'  => $paginator->currentPage(),
            'per_page'      => $paginator->perPage(),
            'last_page'     => $paginator->lastPage(),
            'module'        => $module,
        ]);
    }

    /**
     * GET /admin/modules/{module}/{id}
     *
     * 获取单条记录详情（用于举报对话框展示举报对象内容等场景）。
     */
    public function show(string $module, $id, Request $request)
    {
        $config = self::MODULES[$module] ?? null;
        if (! $config) {
            return response()->json(['message' => 'Unknown module'], 404);
        }

        $modelClass = $config['model'];
        $primaryKey = (new $modelClass)->getKeyName();
        $item = $modelClass::query()->where($primaryKey, $id)->first();

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $array = $item->toArray();

        // 附上 username
        if ($module !== 'user-groups' && ! empty($array['user_id'])) {
            $array['username'] = User::query()->where('user_id', $array['user_id'])->value('username');
        }

        // users 模块扁平化用户组名
        if ($module === 'users' && isset($array['user_group']['user_group_name'])) {
            $array['user_group_name'] = $array['user_group']['user_group_name'];
        }

        return response()->json($array);
    }

    /**
     * POST /admin/modules/{module}
     *
     * 管理员创建单条记录：仅允许 update_fields 白名单内字段。
     * 目前仅 user-groups 模块开放创建路由。
     */
    public function store(string $module, Request $request)
    {
        $config = self::MODULES[$module] ?? null;
        if (! $config || empty($config['update_fields'])) {
            return response()->json(['message' => 'Module not creatable'], 404);
        }

        $modelClass = $config['model'];
        $item = new $modelClass;

        $data = $request->only($config['update_fields']);
        $item->fill($data);
        $item->save();

        $array = $item->toArray();
        if ($module === 'users' && isset($array['user_group']['user_group_name'])) {
            $array['user_group_name'] = $array['user_group']['user_group_name'];
        }

        return response()->json($array, 201);
    }

    /**
     * PUT /admin/modules/{module}/{id}
     *
     * 管理员更新单条记录：仅允许 update_fields 白名单内字段。
     * 管理员权限由路由中间件 ability:ability_admin_manage_* 控制。
     */
    public function update(string $module, $id, Request $request)
    {
        $config = self::MODULES[$module] ?? null;
        if (! $config || empty($config['update_fields'])) {
            return response()->json(['message' => 'Module not editable'], 404);
        }

        $modelClass = $config['model'];
        $primaryKey = (new $modelClass)->getKeyName();
        $item = $modelClass::query()->where($primaryKey, $id)->first();

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // 仅取白名单内字段，防止越权修改其他列
        $data = $request->only($config['update_fields']);

        // users 模块邮箱唯一性校验
        if ($module === 'users' && isset($data['email'])) {
            $exists = User::query()
                ->where('email', $data['email'])
                ->where('user_id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Email already taken', 'errors' => ['email' => ['该邮箱已被使用']]], 422);
            }
        }

        // 记录举报处理前的状态（用于判断是否需要发通知）
        $oldReportState = ($module === 'reports') ? (int) $item->report_handle_state : null;

        $item->fill($data);
        // 软删除字段在 fill 时若被意外传入则忽略，避免误删
        foreach (['delete_time', 'disable_time'] as $protectedField) {
            unset($item->{$protectedField});
        }
        $item->save();

        // 举报模块：处理状态变更后通知举报人
        if ($module === 'reports' && $oldReportState !== null) {
            $newState = (int) $item->report_handle_state;
            if ($newState !== $oldReportState && $newState > 0) {
                $this->notifyReportHandled($item, $newState, $request->user()->user_id);
            }
        }

        // users 模块更新后回带 user_group_name 给前端
        $array = $item->refresh()->toArray();
        if ($module === 'users') {
            $array = $item->refresh()->load(['userGroup'])->toArray();
            if (isset($array['user_group']['user_group_name'])) {
                $array['user_group_name'] = $array['user_group']['user_group_name'];
            }
        }

        return response()->json($array);
    }

    /**
     * 举报处理完成后，向举报人发送站内通知。
     *
     * @param  \App\Models\Report $report     举报记录
     * @param  int                $newState   新处理状态：1=已处理(违规删除) 2=无违规/不予处理
     * @param  int                $adminId    操作管理员 user_id
     */
    private function notifyReportHandled(Report $report, int $newState, int $adminId): void
    {
        $reporterId = (int) $report->user_id;
        if ($reporterId <= 0 || $reporterId === $adminId) {
            return;
        }

        $resultText = $newState === 1
            ? '您的举报已处理：违规内容已处理'
            : '您的举报已处理：未发现违规，不予处理';

        // 根据举报对象类型补齐通知上下文（用于前端跳转）
        $context = [
            'content_markdown' => $resultText,
            'content_rendered' => $resultText,
        ];

        $reportableType = strtolower(str_replace('App\\Models\\', '', $report->reportable_type ?? ''));
        $reportableId   = (int) $report->reportable_id;

        if ($reportableId > 0) {
            switch ($reportableType) {
                case 'question':
                    $context['question_id'] = $reportableId;
                    break;
                case 'article':
                    $context['article_id'] = $reportableId;
                    break;
                case 'answer':
                    $context['answer_id'] = $reportableId;
                    break;
                case 'comment':
                    $context['comment_id'] = $reportableId;
                    break;
                case 'reply':
                    $context['reply_id'] = $reportableId;
                    break;
                case 'topic':
                    $context['topic_id'] = $reportableId;
                    break;
                case 'user':
                    $context['user_id'] = $reportableId;
                    break;
            }
        }

        NotificationService::send($reporterId, $adminId, NotificationService::TYPE_REPORT_HANDLED, $context);
    }

    /**
     * DELETE /admin/modules/{module}/{id}
     *
     * 管理员删除单条记录：优先走软删除（设置 delete_time / disable_time）。
     */
    public function destroy(string $module, $id, Request $request)
    {
        $config = self::MODULES[$module] ?? null;
        if (! $config) {
            return response()->json(['message' => 'Unknown module'], 404);
        }
        if ($module === 'reports') {
            return response()->json(['message' => 'Reports not deletable here'], 404);
        }

        $modelClass = $config['model'];
        $primaryKey = (new $modelClass)->getKeyName();
        $item = $modelClass::query()->where($primaryKey, $id)->first();

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $deleteField = $config['delete_field'] ?? null;
        if (in_array($deleteField, ['delete_time', 'disable_time'], true)) {
            // 软删除：仅设置时间戳，行数据保留以便事后审计
            $item->{$deleteField} = now();
            $item->save();
        } else {
            // 无软删除字段的模块（如 reports）硬删除
            $item->delete();
        }

        return response()->json(null, 204);
    }
}
