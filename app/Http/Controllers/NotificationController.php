<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NotificationController extends Controller
{
    /**
     * 当前登录用户的通知列表，分页 + 时间倒序，附加发送者信息。
     */
    public function index(Request $request)
    {
        $query = Notification::query()
            ->where('receiver_id', $request->user()->user_id)
            ->whereNull('delete_time')
            ->orderByDesc('create_time')
            ->orderByDesc('notification_id');

        [$result, $collection] = $this->paginateOrGet($query, $request);

        $this->appendSenders($collection);

        if ($result !== $collection) {
            $result->setCollection($collection);
        }

        return response()->json($result);
    }

    /**
     * 当前登录用户的未读通知数量（供应用栏徽章轮询）。
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::query()
            ->where('receiver_id', $request->user()->user_id)
            ->whereNull('delete_time')
            ->whereNull('read_time')
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * 将当前用户所有未读通知标记为已读。
     */
    public function markAllRead(Request $request)
    {
        Notification::query()
            ->where('receiver_id', $request->user()->user_id)
            ->whereNull('delete_time')
            ->whereNull('read_time')
            ->update(['read_time' => now()]);

        return response()->json(['updated' => true]);
    }

    /**
     * 清空当前用户的所有通知。
     */
    public function destroyAll(Request $request)
    {
        Notification::query()
            ->where('receiver_id', $request->user()->user_id)
            ->whereNull('delete_time')
            ->update(['delete_time' => now()]);

        return response()->json(['is_deleted' => true]);
    }

    /**
     * 删除单条通知（仅本人）。
     */
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->receiver_id !== $request->user()->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $notification->update(['delete_time' => now()]);

        return response()->json(['is_deleted' => true]);
    }

    /**
     * 给通知集合批量附加发送者（sender_user）信息，避免 N+1。
     */
    protected function appendSenders(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $senderIds = $items->pluck('sender_id')->filter()->unique()->all();
        $senders = $senderIds
            ? User::query()->whereIn('user_id', $senderIds)->get()->keyBy('user_id')
            : collect();

        return $items->each(function ($item) use ($senders) {
            $item->sender_user = $senders->get($item->sender_id) ?: null;
        });
    }
}
