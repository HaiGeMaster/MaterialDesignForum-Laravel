<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
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
     * 文件保存至 storage/app/public/uploads/media/{image|video}，
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

        // 保存到 storage/app/public/uploads/media/{image|video}
        $path = $file->storeAs('uploads/media/'.$type, $filename, 'public');

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
            'url' => '/storage/'.$path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => (int) $width,
            'height' => (int) $height,
        ]);

        return response()->json([
            'message' => '上传成功。',
            'media' => $media,
            'url' => $media->url,
            'path' => $media->path,
            'type' => $media->type,
        ]);
    }

    /**
     * 当前登录用户的媒体库列表（仅本人可见）
     *
     * 支持 ?type=image|video 过滤，按上传时间倒序。
     */
    public function index(Request $request, User $user)
    {
        $current = $request->user();

        // 媒体库仅本人可查看
        if (! $current || (int) $current->user_id !== (int) $user->user_id) {
            return response()->json(['message' => '无权查看该用户的媒体库。'], 403);
        }

        $query = Media::query()
            ->where('user_id', $user->user_id)
            ->orderByDesc('create_time')
            ->orderByDesc('media_id');

        if ($request->query('type')) {
            $query->where('type', $request->query('type'));
        }

        return response()->json($query->limit(500)->get());
    }
}
