<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use App\Models\Inbox;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Token;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    /** @var array 发送者类型 */
    private static array $sender_type = [
        'user_to_user',
        'user_to_chat_group',
        'system_to_user',
        'system_to_user_group',
    ];

    /**
     * 验证发送者类型
     */
    public static function IsVaildSenderType($sender_type): bool
    {
        return in_array($sender_type, self::$sender_type);
    }

    /**
     * 用户获取来自其他用户的消息
     * GET /api/inbox/user
     */
    public function UserGetUserInbox(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_token = $request->input('user_token', $request->bearerToken());
        $order = $request->input('order', 'create_time_desc');
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        $user_id = $this->getUserId($user_token);
        if (!$user_id) {
            return response()->json(['is_get' => false, 'data' => null]);
        }

        return $this->GetInboxResponse($user_id, 'user_to_user', $order, $page, $per_page);
    }

    /**
     * 用户获取来自聊天组的消息
     * GET /api/inbox/chat_group
     */
    public function UserGetChatGroupInbox(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_token = $request->input('user_token', $request->bearerToken());
        $order = $request->input('order', 'create_time_desc');
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        $user_id = $this->getUserId($user_token);
        if (!$user_id) {
            return response()->json(['is_get' => false, 'data' => null]);
        }

        return $this->GetInboxResponse($user_id, 'user_to_chat_group', $order, $page, $per_page);
    }

    /**
     * 用户获取来自用户组的消息
     * GET /api/inbox/user_group
     */
    public function UserGetUserGroupInbox(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_token = $request->input('user_token', $request->bearerToken());
        $order = $request->input('order', 'create_time_desc');
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        $user_id = $this->getUserId($user_token);
        if (!$user_id) {
            return response()->json(['is_get' => false, 'data' => null]);
        }

        return $this->GetInboxResponse($user_id, 'system_to_user_group', $order, $page, $per_page);
    }

    /**
     * 用户获取来自系统的消息
     * GET /api/inbox/system
     */
    public function UserGetSystemInbox(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_token = $request->input('user_token', $request->bearerToken());
        $order = $request->input('order', 'create_time_desc');
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        $user_id = $this->getUserId($user_token);
        if (!$user_id) {
            return response()->json(['is_get' => false, 'data' => null]);
        }

        return $this->GetInboxResponse($user_id, 'system_to_user', $order, $page, $per_page);
    }

    /**
     * 客户端添加消息
     * POST /api/inbox/add
     */
    public function Client_AddInbox(Request $request): \Illuminate\Http\JsonResponse
    {
        $sender_token = $request->input('sender_token', $request->bearerToken());
        $sender_type = $request->input('sender_type');
        $receiver_id = $request->input('receiver_id');
        $content_markdown = $request->input('content_markdown');
        $content_rendered = $request->input('content_rendered');

        $sender_id = $this->getUserId($sender_token);
        if (!$sender_id) {
            return response()->json(['is_add' => false, 'inbox' => null]);
        }

        // 客户端只允许 user_to_user 和 user_to_chat_group
        if ($sender_type != 'user_to_user' && $sender_type != 'user_to_chat_group') {
            return response()->json(['is_add' => false, 'inbox' => null]);
        }

        return response()->json(
            $this->Server_AddInboxInternal($sender_id, $sender_type, $receiver_id, $content_markdown, $content_rendered)
        );
    }

    /**
     * 服务端添加消息（公开 API）
     * POST /api/inbox/server_add
     */
    public function Server_AddInbox(Request $request): \Illuminate\Http\JsonResponse
    {
        $sender_id = $request->input('sender_id');
        $sender_type = $request->input('sender_type');
        $receiver_id = $request->input('receiver_id');
        $content_markdown = $request->input('content_markdown');
        $content_rendered = $request->input('content_rendered');

        return response()->json(
            $this->Server_AddInboxInternal($sender_id, $sender_type, $receiver_id, $content_markdown, $content_rendered)
        );
    }

    // ==================== 内部方法 ====================

    /**
     * 服务端添加消息（内部逻辑）
     */
    private function Server_AddInboxInternal($sender_id, $sender_type, $receiver_id, $content_markdown, $content_rendered): array
    {
        $is_valid = $sender_id && $sender_type && $receiver_id && $content_markdown && $content_rendered;
        $is_add = false;
        $inbox = null;

        if ($is_valid && self::IsVaildSenderType($sender_type)) {
            $is_valid_user = false;

            switch ($sender_type) {
                case 'user_to_user':
                    $is_valid_user = User::find($sender_id) && User::find($receiver_id);
                    break;
                case 'user_to_chat_group':
                    $is_valid_user = User::find($sender_id) !== null;
                    break;
                case 'system_to_user':
                    $is_valid_user = User::find($receiver_id) !== null;
                    break;
                case 'system_to_user_group':
                    $is_valid_user = UserGroup::find($receiver_id) !== null;
                    break;
            }

            if ($is_valid_user) {
                $inbox = new Inbox();
                $inbox->sender_id = $sender_id;
                $inbox->sender_type = $sender_type;
                $inbox->receiver_id = $receiver_id;
                $inbox->content_markdown = $content_markdown;
                $inbox->content_rendered = $content_rendered;
                $inbox->create_time = time();
                $is_add = $inbox->save();

                if ($is_add) {
                    User::where('user_id', $receiver_id)->increment('notification_unread');
                }
            }
        }

        return ['is_add' => $is_add, 'inbox' => $inbox];
    }

    /**
     * 获取消息箱响应
     */
    private function GetInboxResponse($receiver_id, string $sender_type, string $order, int $page, int $per_page): \Illuminate\Http\JsonResponse
    {
        $orderParts = explode('_', $order);
        $field = $orderParts[0] ?? 'create_time';
        $sort = $orderParts[1] ?? 'desc';

        if (!$receiver_id || !self::IsVaildSenderType($sender_type)) {
            return response()->json(['is_get' => false, 'data' => null]);
        }

        $paginator = Inbox::where('receiver_id', $receiver_id)
            ->where('sender_type', $sender_type)
            ->orderBy($field, $sort)
            ->paginate($per_page, ['*'], 'page', $page);

        $data = [
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];

        return response()->json([
            'is_get' => $data !== null,
            'data' => $data,
        ]);
    }

    /**
     * 从 token 获取用户 ID
     */
    private function getUserId(?string $token): ?int
    {
        if (empty($token)) return null;
        $t = Token::where('token', $token)->first();
        return ($t && $t->expire_time > time()) ? $t->user_id : null;
    }
}
