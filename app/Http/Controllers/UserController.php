<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Question;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * 用户列表
     *
     * tab 过滤：
     * - following  我关注的用户（需登录）
     * - followers  我的粉丝（需登录）
     * - new        最近加入的用户（按注册时间倒序）
     * - recommend  推荐人脉（默认，按粉丝数排序）
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'recommend');

        $query = User::query()->whereNotDisabled();

        // 用户名搜索（@提及自动补全使用）
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where('username', 'like', $q . '%');
        }

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if ($tab === 'following') {
            if (! $user) {
                return response()->json([]);
            }
            $ids = DB::table('follow')
                ->where('user_id', $user->user_id)
                ->where('followable_type', 'user')
                ->pluck('followable_id');
            $query->whereIn('user_id', $ids);
        } elseif ($tab === 'followers') {
            if (! $user) {
                return response()->json([]);
            }
            $ids = DB::table('follow')
                ->where('followable_type', 'user')
                ->where('followable_id', $user->user_id)
                ->pluck('user_id');
            $query->whereIn('user_id', $ids);
        }

        // new：按注册时间倒序；其余：按粉丝数倒序
        if ($tab === 'new') {
            $query->orderByDesc('create_time')->orderByDesc('user_id');
        } else {
            $query->orderByDesc('follower_count')->orderByDesc('user_id');
        }

        // 带 page 参数时分页返回，否则保持完整数组（旧调用不受影响）
        [$result, $collection] = $this->paginateOrGet($query, $request);

        $this->attachUserCounts($collection);
        $this->appendFollowStatus($collection, $request, 'user', 'user_id');

        if ($result !== $collection) {
            $result->setCollection($collection);
        }

        return response()->json($result);
    }

    /**
     * 新增用户（后台管理用）
     */
    public function store(Request $request)
    {
        $user = User::create($request->all());

        return response()->json($user, 201);
    }

    /**
     * 用户详情
     */
    public function show(Request $request, User $user)
    {
        // 加载用户组，供前端展示用户组标识（他人主页同样需要）
        $user->load('userGroup');

        $this->attachUserCounts(collect([$user]));
        $isDisabled = $user->isDisabled();
        $user->setAttribute('is_disabled', $isDisabled);
        $user->setAttribute('system_email', config('mail.from.address'));

        return response()->json($this->appendFollowStatusToModel($user, $request, 'user'));
    }

    /**
     * 更新用户（后台管理用）
     */
    public function update(Request $request, User $user)
    {
        $user->fill($request->all());
        $user->save();

        return response()->json($user);
    }

    /**
     * 删除用户（后台管理用）
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * 指定用户的提问列表
     *
     * 带 page 参数时分页返回，否则保持完整数组（兼容旧调用）。
     */
    public function questions(Request $request, User $user)
    {
        $query = Question::query()
            ->where('user_id', $user->user_id)
            ->whereNull('delete_time')
            ->orderByDesc('question_id');

        return response()->json($this->paginateOrGet($query, $request)[0]);
    }

    /**
     * 指定用户的文章列表
     *
     * 带 page 参数时分页返回，否则保持完整数组（兼容旧调用）。
     */
    public function articles(Request $request, User $user)
    {
        $query = Article::query()
            ->where('user_id', $user->user_id)
            ->whereNull('delete_time')
            ->orderByDesc('article_id');

        return response()->json($this->paginateOrGet($query, $request)[0]);
    }

    /**
     * 指定用户的回答列表
     *
     * 附带回答问题标题，便于前端展示；
     * 带 page 参数时分页返回，否则保持完整数组（兼容旧调用）。
     */
    public function answers(Request $request, User $user)
    {
        $query = Answer::query()
            ->where('user_id', $user->user_id)
            ->whereNull('delete_time')
            ->orderByDesc('answer_id');

        [$result, $collection] = $this->paginateOrGet($query, $request);

        // 补充每个回答所属问题的标题
        $questionTitles = Question::query()
            ->whereIn('question_id', $collection->pluck('question_id'))
            ->pluck('title', 'question_id');

        $collection->each(function ($answer) use ($questionTitles) {
            $answer->question_title = $questionTitles[$answer->question_id] ?? null;
        });

        return response()->json($result);
    }

    /**
     * 上传图片（头像 / 用户封面 / 话题封面等）
     *
     * 校验图片类型与大小后存入 MEDIA_DISK 指定的磁盘，返回可访问 URL。
     * 可选参数 context 区分关联场景（默认 user），item_id 指定关联 ID。
     */
    public function uploadImage(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:avatar,cover'],
            'file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
            'context' => ['sometimes', 'string', 'in:user,topic'],
            'item_id' => ['sometimes', 'integer'],
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        // 随机文件名，避免重名覆盖与路径穿越
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::random(24).'.'.$extension;

        $diskName = MediaStorage::diskName();
        $disk = MediaStorage::disk();
        $path = $disk->putFileAs('uploads/'.$data['type'], $file, $filename);
        if (! is_string($path)) {
            throw new \RuntimeException('Unable to store uploaded image.');
        }
        $url = MediaStorage::url($diskName, $disk, $path);

        // 读取原始尺寸
        $size = @getimagesize($file->getRealPath());
        [$width, $height] = is_array($size) ? [$size[0], $size[1]] : [0, 0];

        // 根据 context 确定关联类型与关联 ID
        $context = $data['context'] ?? 'user';
        if ($context === 'topic') {
            $itemType = 'topic_cover';
            $itemId = $data['item_id'] ?? 0;
        } else {
            $itemType = $data['type'] === 'avatar' ? 'user_avatar' : 'user_cover';
            $itemId = $user->user_id;
        }

        \App\Models\Media::create([
            'user_id' => $user->user_id,
            'type' => 'image',
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => $url,
            'storage_disk' => $diskName,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => (int) $width,
            'height' => (int) $height,
            'item_type' => $itemType,
            'item_id' => $itemId,
        ]);

        return response()->json([
            'message' => __('messages.http_media_1'),
            'url' => $url,
            'path' => $path,
            'type' => $data['type'],
        ]);
    }

    /**
     * 编辑当前登录用户的个人资料
     *
     * 仅允许修改展示类字段（头像、封面、简介等），
     * username / email 允许修改但需保证唯一。
     * 该接口受路由层 CheckAbility:ability_edit_own_info 权限保护。
     */
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'username' => ['sometimes', 'string', 'max:30', Rule::unique('user', 'username')->ignore($user->user_id, 'user_id')],
            'email' => ['sometimes', 'email', 'max:320', Rule::unique('user', 'email')->ignore($user->user_id, 'user_id')],
            'avatar' => ['nullable', 'string', 'max:2000'],
            'cover' => ['nullable', 'string', 'max:2000'],
            'headline' => ['nullable', 'string', 'max:40'],
            'bio' => ['nullable', 'string', 'max:160'],
            'blog' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:30'],
        ]);

        $user->fill($data);
        $user->update_time = now();
        $user->save();

        return response()->json([
            'message' => __('messages.http_user_1'),
            'user' => $user->load('userGroup'),
        ]);
    }

    /**
     * 修改当前登录用户的密码
     *
     * 需提供旧密码校验，新密码需二次确认。
     */
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'old_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($data['old_password'], $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => [__('messages.http_user_3')],
            ]);
        }

        $user->password = Hash::make($data['password']);
        $user->update_time = now();
        $user->save();

        return response()->json(['message' => __('messages.http_user_2')]);
    }

    /**
     * 获取当前用户的登录设备凭证列表。
     */
    public function tokens(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $currentToken = $this->extractBearerToken($request);

        $items = $user->tokens()
            ->orderByDesc('update_time')
            ->get()
            ->map(function ($token) use ($currentToken) {
                return [
                    'token' => $token->token,
                    'device' => $token->device ?: 'Unknown device',
                    'create_time' => $token->create_time?->toDateTimeString(),
                    'update_time' => $token->update_time?->toDateTimeString(),
                    'expire_time' => $token->expire_time?->toDateTimeString(),
                    'is_current' => $token->token === $currentToken,
                ];
            });

        return response()->json([
            'data' => $items,
            'current_token' => $currentToken,
        ]);
    }

    /**
     * 注销某个设备的登录凭证。
     */
    public function revokeToken(Request $request, string $token)
    {
        /** @var User $user */
        $user = $request->user();
        $currentToken = $this->extractBearerToken($request);

        if ($token === $currentToken) {
            return response()->json([
                'message' => __('messages.http_user_4'),
            ], 422);
        }

        $deleted = $user->tokens()->where('token', $token)->delete();

        if (! $deleted) {
            return response()->json([
                'message' => __('messages.http_user_5'),
            ], 404);
        }

        return response()->json([
            'message' => __('messages.http_user_6'),
        ]);
    }

    /**
     * 注销当前用户除当前设备外的所有登录凭证。
     */
    public function revokeOtherTokens(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $currentToken = $this->extractBearerToken($request);

        $user->tokens()
            ->when($currentToken, fn ($query) => $query->where('token', '!=', $currentToken))
            ->delete();

        return response()->json([
            'message' => __('messages.http_user_7'),
        ]);
    }

    protected function extractBearerToken(Request $request): ?string
    {
        $header = (string) $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
