<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 通知服务
 *
 * 统一封装站内通知与邮件通知：
 * - 定义系统全部通知类型（与前端语言文件 notificationType 一一对应）；
 * - send()         单发：关注 / 点赞 / 评论 / 回复 / 回答 / 删除等行为；
 * - sendToMany()   群发：关注推送（关注的人/话题有新内容）等场景。
 *
 * 发送前会读取接收者在 user_option（name=notifications）中的设置，
 * 分别决定是否发送站内通知（web_message）与邮件通知（email_message）。
 * 站内通知写入 notification 表；邮件通知通过 NotificationMail 发送，
 * 发送失败仅记录日志，不影响主流程。
 */
class NotificationService
{
    // ---------- 通知类型（24 种） ----------

    public const TYPE_USER_FOLLOW = 'user_follow';
    public const TYPE_TOPIC_FOLLOW = 'topic_follow';
    public const TYPE_TOPIC_DELETE = 'topic_delete';
    public const TYPE_QUESTION_FOLLOW = 'question_follow';
    public const TYPE_QUESTION_COMMENT = 'question_comment';
    public const TYPE_QUESTION_ANSWER = 'question_answer';
    public const TYPE_QUESTION_DELETE = 'question_delete';
    public const TYPE_ARTICLE_FOLLOW = 'article_follow';
    public const TYPE_ARTICLE_COMMENT = 'article_comment';
    public const TYPE_ARTICLE_LIKE = 'article_like';
    public const TYPE_ARTICLE_DELETE = 'article_delete';
    public const TYPE_ANSWER_COMMENT = 'answer_comment';
    public const TYPE_ANSWER_LIKE = 'answer_like';
    public const TYPE_ANSWER_DELETE = 'answer_delete';
    public const TYPE_COMMENT_LIKE = 'comment_like';
    public const TYPE_COMMENT_REPLY = 'comment_reply';
    public const TYPE_COMMENT_DELETE = 'comment_delete';
    public const TYPE_REPLY_LIKE = 'reply_like';
    public const TYPE_REPLY_REPLY = 'reply_reply';
    public const TYPE_REPLY_DELETE = 'reply_delete';
    public const TYPE_FOLLOW_USER_UPDATE = 'follow_user_update';
    public const TYPE_FOLLOW_TOPIC_UPDATE = 'follow_topic_update';
    public const TYPE_FOLLOW_QUESTION_UPDATE = 'follow_question_update';
    public const TYPE_FOLLOW_ARTICLE_UPDATE = 'follow_article_update';
    public const TYPE_REPORT_HANDLED = 'report_handled';

    /** 全部通知类型 */
    public const TYPES = [
        self::TYPE_USER_FOLLOW,
        self::TYPE_TOPIC_FOLLOW,
        self::TYPE_TOPIC_DELETE,
        self::TYPE_QUESTION_FOLLOW,
        self::TYPE_QUESTION_COMMENT,
        self::TYPE_QUESTION_ANSWER,
        self::TYPE_QUESTION_DELETE,
        self::TYPE_ARTICLE_FOLLOW,
        self::TYPE_ARTICLE_COMMENT,
        self::TYPE_ARTICLE_LIKE,
        self::TYPE_ARTICLE_DELETE,
        self::TYPE_ANSWER_COMMENT,
        self::TYPE_ANSWER_LIKE,
        self::TYPE_ANSWER_DELETE,
        self::TYPE_COMMENT_LIKE,
        self::TYPE_COMMENT_REPLY,
        self::TYPE_COMMENT_DELETE,
        self::TYPE_REPLY_LIKE,
        self::TYPE_REPLY_REPLY,
        self::TYPE_REPLY_DELETE,
        self::TYPE_FOLLOW_USER_UPDATE,
        self::TYPE_FOLLOW_TOPIC_UPDATE,
        self::TYPE_FOLLOW_QUESTION_UPDATE,
        self::TYPE_FOLLOW_ARTICLE_UPDATE,
        self::TYPE_REPORT_HANDLED,
    ];

    /** 类型 → 中文文案模板（{value} 为用户名字符串，用于邮件正文 / 兜底展示） */
    public const TEMPLATES = [
        self::TYPE_USER_FOLLOW => '{value} 关注了您',
        self::TYPE_TOPIC_FOLLOW => '{value} 关注了您的话题',
        self::TYPE_TOPIC_DELETE => '{value} 删除了您的话题',
        self::TYPE_QUESTION_FOLLOW => '{value} 关注了您的提问',
        self::TYPE_QUESTION_COMMENT => '{value} 评论了您的提问',
        self::TYPE_QUESTION_ANSWER => '{value} 回答了您的提问',
        self::TYPE_QUESTION_DELETE => '{value} 删除了您的提问',
        self::TYPE_ARTICLE_FOLLOW => '{value} 关注了您的文章',
        self::TYPE_ARTICLE_COMMENT => '{value} 评论了您的文章',
        self::TYPE_ARTICLE_LIKE => '{value} 点赞了您的文章',
        self::TYPE_ARTICLE_DELETE => '{value} 删除了您的文章',
        self::TYPE_ANSWER_COMMENT => '{value} 评论了您的回答',
        self::TYPE_ANSWER_LIKE => '{value} 点赞了您的回答',
        self::TYPE_ANSWER_DELETE => '{value} 删除了您的回答',
        self::TYPE_COMMENT_LIKE => '{value} 点赞了您的评论',
        self::TYPE_COMMENT_REPLY => '{value} 回复了您的评论',
        self::TYPE_COMMENT_DELETE => '{value} 删除了您的评论',
        self::TYPE_REPLY_LIKE => '{value} 点赞了您的回复',
        self::TYPE_REPLY_REPLY => '{value} 回复了您的回复',
        self::TYPE_REPLY_DELETE => '{value} 删除了您的回复',
        self::TYPE_FOLLOW_USER_UPDATE => '您关注的用户有更新 {value}',
        self::TYPE_FOLLOW_TOPIC_UPDATE => '您关注的话题有更新 {value}',
        self::TYPE_FOLLOW_QUESTION_UPDATE => '您关注的提问有更新 {value}',
        self::TYPE_FOLLOW_ARTICLE_UPDATE => '您关注的文章有更新 {value}',
        self::TYPE_REPORT_HANDLED => '{value} 您的举报已处理',
    ];

    /** 用户通知设置在 user_option 表中的 name */
    private const OPTION_NAME = 'notifications';

    /**
     * 单发通知。
     *
     * @param int    $receiverId 接收者（user_id）
     * @param int    $senderId   行为发起者（user_id）
     * @param string $type       通知类型（见 TYPES）
     * @param array  $context    关联上下文，可含：
     *                           user_id / topic_id / article_id / question_id /
     *                           answer_id / comment_id / reply_id / reply_to_reply_id
     *                           content_markdown / content_rendered
     */
    public static function send(int $receiverId, int $senderId, string $type, array $context = []): void
    {
        // 基础过滤：接收者有效、发起者有效、不能是给自己发
        if ($receiverId <= 0 || $senderId <= 0 || $receiverId === $senderId) {
            return;
        }
        if (! in_array($type, self::TYPES, true)) {
            return;
        }

        $prefs = self::preferences($receiverId, $type);

        if ($prefs['web']) {
            self::createInbox($receiverId, $senderId, $type, $context);
        }

        if ($prefs['email']) {
            self::sendEmail($receiverId, $senderId, $type, $context);
        }
    }

    /**
     * 群发通知（关注推送等）。自动去重并排除发起者自身。
     *
     * @param iterable $receiverIds 接收者 user_id 列表
     * @param int      $senderId    行为发起者（user_id）
     * @param string   $type        通知类型
     * @param array    $context     关联上下文（同 send）
     */
    public static function sendToMany(iterable $receiverIds, int $senderId, string $type, array $context = []): void
    {
        if (! in_array($type, self::TYPES, true)) {
            return;
        }

        $ids = collect($receiverIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== $senderId)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        foreach ($ids as $receiverId) {
            self::send($receiverId, $senderId, $type, $context);
        }
    }

    /**
     * 将评论解析为通知上下文（补齐所属内容 id）。
     * 用于评论/回复类通知的跳转定位。
     */
    public static function contextForComment(int $commentId): array
    {
        $comment = DB::table('comment')->where('comment_id', $commentId)->first();
        if (! $comment) {
            return [];
        }

        return self::contextForContent($comment->commentable_type, $comment->commentable_id, [
            'comment_id' => $commentId,
            'content_rendered' => $comment->content ?? null,
        ]);
    }

    /**
     * 根据内容类型与 id 补齐通知上下文（含所属提问/文章/回答）。
     */
    public static function contextForContent(string $type, int $id, array $base = []): array
    {
        $context = $base;

        switch ($type) {
            case 'article':
                $context['article_id'] = $id;
                break;
            case 'question':
                $context['question_id'] = $id;
                break;
            case 'answer':
                $context['answer_id'] = $id;
                $questionId = (int) DB::table('answer')->where('answer_id', $id)->value('question_id');
                if ($questionId > 0) {
                    $context['question_id'] = $questionId;
                }
                break;
        }

        return $context;
    }

    // ---------- 内部实现 ----------

    /**
     * 读取接收者对某类型的站内 / 邮件开关。
     * 未配置时默认都开启。
     *
     * @return array{web: bool, email: bool}
     */
    private static function preferences(int $userId, string $type): array
    {
        $option = UserOption::query()
            ->where('user_id', $userId)
            ->where('name', self::OPTION_NAME)
            ->first();

        $web = true;
        $email = true;

        if ($option && $option->value) {
            $items = json_decode($option->value, true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (($item['name'] ?? '') === $type) {
                        $web = (bool) ($item['web_message'] ?? true);
                        $email = (bool) ($item['email_message'] ?? true);
                        break;
                    }
                }
            }
        }

        return ['web' => $web, 'email' => $email];
    }

    /**
     * 写入站内通知（notification 表）。
     */
    private static function createInbox(int $receiverId, int $senderId, string $type, array $context): void
    {
        Notification::create([
            'receiver_id' => $receiverId,
            'sender_id' => $senderId,
            'type' => $type,
            'content_markdown' => $context['content_markdown'] ?? null,
            'content_rendered' => $context['content_rendered'] ?? null,
            'user_id' => $context['user_id'] ?? 0,
            'topic_id' => $context['topic_id'] ?? 0,
            'article_id' => $context['article_id'] ?? 0,
            'question_id' => $context['question_id'] ?? 0,
            'answer_id' => $context['answer_id'] ?? 0,
            'comment_id' => $context['comment_id'] ?? 0,
            'reply_id' => $context['reply_id'] ?? 0,
            'reply_to_reply_id' => $context['reply_to_reply_id'] ?? 0,
        ]);
    }

    /**
     * 发送邮件通知。任何失败仅记录日志，不影响主流程。
     */
    private static function sendEmail(int $receiverId, int $senderId, string $type, array $context): void
    {
        try {
            $receiver = User::query()->find($receiverId);
            $sender = User::query()->find($senderId);
            if (! $receiver || ! $receiver->email || ! $sender) {
                return;
            }

            $template = self::TEMPLATES[$type] ?? $type;
            $message = str_replace('{value}', $sender->username, $template);

            // 关联内容链接（可选）
            $url = self::buildContentUrl($context);
            if ($url) {
                $message .= "\n\n" . $url;
            }

            Mail::to($receiver->email)->send(new NotificationMail(
                $message,
                $sender->username,
                $type,
                (string) config('app.name'),
            ));
        } catch (\Throwable $e) {
            Log::warning('发送通知邮件失败', [
                'receiver_id' => $receiverId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 根据上下文生成内容跳转链接。
     */
    private static function buildContentUrl(array $context): ?string
    {
        $base = rtrim((string) config('app.url'), '/');

        return match (true) {
            ! empty($context['question_id']) => $base . '/questions/' . $context['question_id'],
            ! empty($context['article_id']) => $base . '/articles/' . $context['article_id'],
            ! empty($context['topic_id']) => $base . '/topics/' . $context['topic_id'],
            ! empty($context['user_id']) => $base . '/users/' . $context['user_id'],
            default => null,
        };
    }
}
