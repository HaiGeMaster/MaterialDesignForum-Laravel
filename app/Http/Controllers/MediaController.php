<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 富文本媒体控制器
 *
 * 负责编辑器内图片 / 视频的上传（POST /media，需登录）
 * 以及用户媒体库的列表查询（GET /users/{user}/media）。
 */
class MediaController extends Controller
{
    /**
     * 上传媒体（图片 / 视频）
     *
     * 文件保存至 public/uploads/media/{image|video}，
     * 并将元数据写入 media 表，返回可访问 URL 供编辑器插入。
     */
    public function upload(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:image,video'],
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $type = $data['type'];
        $file = $request->file('file');

        if ($type === 'image') {
            $mimes = 'jpeg,jpg,png,webp,gif';
            $maxSize = 10240; // 10MB
        } else {
            $mimes = 'mp4,webm,ogg,mov,m4v';
            $maxSize = 102400; // 100MB
        }

        $validated = $request->validate([
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxSize}"],
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['file'];

        // 随机文件名，避免重名覆盖与路径穿越
        $extension = $file->getClientOriginalExtension() ?: ($type === 'image' ? 'jpg' : 'mp4');
        $filename = Str::random(24).'.'.$extension;

        // 保存到 public/uploads/media/{image|video}
        $path = $file->storeAs('uploads/media/'.$type, $filename, 'uploads');

        // 图片读取原始尺寸；视频尺寸未知时记为 0
        $width = 0;
        $height = 0;
        if ($type === 'image') {
            $size = @getimagesize($file->getRealPath());
            if (is_array($size)) {
                [$width, $height] = [$size[0], $size[1]];
            }
        }

        $media = Media::create([
            'user_id' => $user->user_id,
            'type' => $type,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => '/'.$path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => (int) $width,
            'height' => (int) $height,
        ]);

        return response()->json([
            'message' => __('messages.http_media_1'),
            'media' => $media,
            'url' => $media->url,
            'path' => $media->path,
            'type' => $media->type,
        ]);
    }

    /**
     * 当前登录用户的媒体库列表（仅本人可见）
     *
     * 支持筛选参数：
     *  - type       : image | video（媒体类型）
     *  - item_type  : question / answer / article / user_cover / user_avatar / topic_cover
     *  - search     : 模糊搜索文件名
     *  - date_from  : 上传时间起始（YYYY-MM-DD）
     *  - date_to    : 上传时间截止（YYYY-MM-DD）
     * 带 page 参数时分页返回，否则保持原有上限完整返回（兼容旧调用）。
     */
    public function index(Request $request, User $user)
    {
        $current = $request->user();

        // 媒体库仅本人可查看
        if (! $current || (int) $current->user_id !== (int) $user->user_id) {
            return response()->json(['message' => __('messages.http_media_2')], 403);
        }

        $query = Media::query()
            ->where('user_id', $user->user_id)
            ->orderByDesc('create_time')
            ->orderByDesc('media_id');

        if ($request->query('type')) {
            $query->where('type', $request->query('type'));
        }

        // 关联类型筛选
        if ($request->query('item_type')) {
            $query->where('item_type', $request->query('item_type'));
        }

        // 文件名模糊搜索
        if ($request->query('search')) {
            $query->where('filename', 'like', '%' . $request->query('search') . '%');
        }

        // 日期范围筛选
        if ($request->query('date_from')) {
            $query->whereDate('create_time', '>=', $request->query('date_from'));
        }
        if ($request->query('date_to')) {
            $query->whereDate('create_time', '<=', $request->query('date_to'));
        }

        // 不分页时保留原有 500 条上限
        if (! $request->has('page')) {
            $query->limit(500);
        }

        return response()->json($this->paginateOrGet($query, $request)[0]);
    }

    /**
     * 批量删除媒体（仅本人），同时删除物理文件。
     *
     * 入参：media_ids 数组；返回实际删除数量。
     */
    public function destroy(Request $request)
    {
        $data = $request->validate([
            'media_ids' => ['required', 'array'],
            'media_ids.*' => ['required', 'integer'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $ids = array_unique(array_map('intval', $data['media_ids']));

        $mediaList = Media::query()
            ->where('user_id', $user->user_id)
            ->whereIn('media_id', $ids)
            ->get();

        $deleted = 0;
        foreach ($mediaList as $media) {
            Storage::disk('uploads')->delete($media->path);
            $media->delete();
            $deleted++;
        }

        return response()->json([
            'message' => __('messages.http_media_3', ['deleted' => $deleted]),
            'deleted' => $deleted,
        ]);
    }
}
