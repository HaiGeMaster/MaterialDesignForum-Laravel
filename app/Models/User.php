<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Models;

use App\Http\Controllers\ImageController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'user_group_id',
        'username',
        'email',
        'avatar',
        'cover',
        'password',
        'create_ip',
        'create_location',
        'last_login_time',
        'last_login_ip',
        'last_login_location',
        'follower_count',
        'followee_count',
        'following_topic_count',
        'following_article_count',
        'following_question_count',
        'topic_count',
        'article_count',
        'question_count',
        'answer_count',
        'comment_count',
        'reply_count',
        'notification_unread',
        'inbox_system',
        'inbox_user_group',
        'inbox_private_message',
        'headline',
        'bio',
        'blog',
        'company',
        'location',
        'language',
        'create_time',
        'update_time',
        'disable_time',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'avatar' => 'array', //这个字段的值将会被自动转换为 PHP 数组：
        'cover' => 'array',
        'user_id' => 'integer',
        'user_group_id' => 'integer',
        'follower_count' => 'integer',
        'followee_count' => 'integer',
        'following_topic_count' => 'integer',
        'following_article_count' => 'integer',
        'following_question_count' => 'integer',
        'topic_count' => 'integer',
        'article_count' => 'integer',
        'question_count' => 'integer',
        'answer_count' => 'integer',
        'comment_count' => 'integer',
        'reply_count' => 'integer',
        'notification_unread' => 'integer',
        'inbox_system' => 'integer',
        'inbox_user_group' => 'integer',
        'inbox_private_message' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'disable_time' => 'datetime',
        'last_login_time' => 'datetime',
        // 'password' => 'hashed',//使用自定义的哈希算法
    ];


    // public function userGroup(): BelongsTo
    // {
    //     return $this->belongsTo(UserGroup::class, 'user_group_id', 'user_group_id');
    // }

    // public function articles(): HasMany
    // {
    //     return $this->hasMany(Article::class, 'user_id', 'user_id');
    // }

    // public function questions(): HasMany
    // {
    //     return $this->hasMany(Question::class, 'user_id', 'user_id');
    // }

    // public function answers(): HasMany
    // {
    //     return $this->hasMany(Answer::class, 'user_id', 'user_id');
    // }

    // public function comments(): HasMany
    // {
    //     return $this->hasMany(Comment::class, 'user_id', 'user_id');
    // }

    // public function replies(): HasMany
    // {
    //     return $this->hasMany(Reply::class, 'user_id', 'user_id');
    // }

    // public function follows(): HasMany
    // {
    //     return $this->hasMany(Follow::class, 'user_id', 'user_id');
    // }

    // public function votes(): HasMany
    // {
    //     return $this->hasMany(Vote::class, 'user_id', 'user_id');
    // }

    // public function notifications(): HasMany
    // {
    //     return $this->hasMany(Notification::class, 'user_id', 'user_id');
    // }

    // 搜索字段
    public static array $search_field = [
        'username',
        'headline',
        'bio'
    ];
    /**
     * 添加用户的 关注我的人数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddFollowerCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->follower_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我关注的人数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddFolloweeCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->followee_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我关注的话题数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddFollowingTopicCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->following_topic_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我关注的文章数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddFollowingArticleCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->following_article_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我关注的问题数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddFollowingQuestionCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->following_question_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我的发表的话题数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddTopicCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->topic_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我的发表的文章数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddArticleCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->article_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我的发表的问题数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddQuestionCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->question_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我的发表的回答数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddAnswerCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->answer_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我的发表的评论数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddCommentCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->comment_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我的发表的回复数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddReplyCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->reply_count += $count;
        return $user->save();
    }
    /**
     * 添加用户的 我的未读通知数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function AddNotificationCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        $user->notification_unread += $count;
        return $user->save();
    }
  // /**
  //  * 添加用户的 我的未读系统消息数
  //  * @param int $user_id 用户ID
  //  * @param int $count 数量
  //  * @return bool
  //  */
  // public static function AddInboxSystem($user_id, $count = 1): bool
  // {
  //   $user = self::find($user_id);
  //   $user->inbox_system += $count;
  //   return $user->save();
  // }
  // /**
  //  * 添加用户的 我的未读用户组消息数
  //  * @param int $user_id 用户ID
  //  * @param int $count 数量
  //  * @return bool
  //  */
  // public static function AddInboxUserGroup($user_id, $count = 1): bool
  // {
  //   $user = self::find($user_id);
  //   $user->inbox_user_group += $count;
  //   return $user->save();
  // }
  // /**
  //  * 添加用户的 我的私信数
  //  * @param int $user_id 用户ID
  //  * @param int $count 数量
  //  * @return bool
  //  */
  // public static function AddInboxPrivateMessage($user_id, $count = 1): bool
  // {
  //   $user = self::find($user_id);
  //   $user->inbox_private_message += $count;
  //   return $user->save();
  // }
    /**
     * 减少用户的 关注我的人数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubFollowerCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->follower_count <= 0) {
            $user->follower_count = 0;
            return $user->save();
        }
        $user->follower_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我关注的人数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubFolloweeCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->followee_count <= 0) {
            $user->followee_count = 0;
            return $user->save();
        }
        $user->followee_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我关注的话题数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubFollowingTopicCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->following_topic_count <= 0) {
            $user->following_topic_count = 0;
            return $user->save();
        }
        $user->following_topic_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我关注的文章数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubFollowingArticleCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->following_article_count <= 0) {
            $user->following_article_count = 0;
            return $user->save();
        }
        $user->following_article_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我关注的问题数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubFollowingQuestionCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->following_question_count <= 0) {
            $user->following_question_count = 0;
            return $user->save();
        }
        $user->following_question_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我的发表的话题数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubTopicCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->topic_count <= 0) {
            $user->topic_count = 0;
            return $user->save();
        }
        $user->topic_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我的发表的文章数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubArticleCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->article_count <= 0) {
            $user->article_count = 0;
            return $user->save();
        }
        $user->article_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我的发表的问题数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubQuestionCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->question_count <= 0) {
            $user->question_count = 0;
            return $user->save();
        }
        $user->question_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我的发表的回答数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubAnswerCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->answer_count <= 0) {
            $user->answer_count = 0;
            return $user->save();
        }
        $user->answer_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我的发表的评论数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubCommentCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->comment_count <= 0) {
            $user->comment_count = 0;
            return $user->save();
        }
        $user->comment_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我的发表的回复数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubReplyCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->reply_count <= 0) {
            $user->reply_count = 0;
            return $user->save();
        }
        $user->reply_count -= $count;
        return $user->save();
    }
    /**
     * 减少用户的 我的未读通知数
     * @param int $user_id 用户ID
     * @param int $count 数量
     * @return bool
     */
    public static function SubNotificationCount($user_id, $count = 1): bool
    {
        $user = self::find($user_id);
        if ($user->notification_unread <= 0) {
            $user->notification_unread = 0;
            return $user->save();
        }
        $user->notification_unread -= $count;
        return $user->save();
    }
  // /**
  //  * 减少用户的 我的系统消息数
  //  * @param int $user_id 用户ID
  //  * @param int $count 数量
  //  * @return bool
  //  */
  // public static function SubInboxSystem($user_id, $count = 1): bool
  // {
  //   $user = self::find($user_id);
  //   if ($user->inbox_system <= 0){
  //     $user->inbox_system = 0;
  //     return $user->save();
  //   }
  //   $user->inbox_system -= $count;
  //   return $user->save();
  // }
  // /**
  //  * 减少用户的 我的用户组消息数
  //  * @param int $user_id 用户ID
  //  * @param int $count 数量
  //  * @return bool
  //  */
  // public static function SubInboxUserGroup($user_id, $count = 1): bool
  // {
  //   $user = self::find($user_id);
  //   if ($user->inbox_user_group <= 0){
  //     $user->inbox_user_group = 0;
  //     return $user->save();
  //   $user->inbox_user_group -= $count;
  //   return $user->save();
  // }
  // /**
  //  * 减少用户的 我的私信数
  //  * @param int $user_id 用户ID
  //  * @param int $count 数量
  //  * @return bool
  //  */
  // public static function SubInboxPrivateMessage($user_id, $count = 1): bool
  // {
  //   $user = self::find($user_id);
  //   if ($user->inbox_private_message <= 0){
  //     $user->inbox_private_message = 0;
  //     return $user->save();
  //   }
  //   $user->inbox_private_message -= $count;
  //   return $user->save();
  // }
    // /**
    //  * 处理密码
    //  * @param string $password 密码
    //  * @return string 处理后的密码
    //  */
    // public static function HandlePassword($password)
    // {
    //     //return password_hash($password, PASSWORD_DEFAULT);
    //     // return md5($password);
    //     return Hash::make($password);
    // }
    /**
     * 验证密码
     * @param string $password 密码
     * @param string $hash 处理后的密码
     * @return bool 是否验证通过
     */
    public static function PasswordHash($password, $hash)
    {
        return password_verify($password, $hash);
    }
    /**
     * 处理用户头像字符串
     * @param string $avatar 用户头像字符串
     * @return array 用户头像数组
     */
    public static function AvatarStringToArray($avatar)
    {
        return json_decode($avatar, true);
    }

    /**
     * 创建默认封面
     * @return array 默认封面url数组
     */
    public static function CreateDefaultCover(): array
    {
        return ImageController::CreateUserDefaultCover();
    }

    /**
     * 创建默认头像
     * @param string $name 用户名
     * @param string|int $user_id 用户id
     * @return array 默认头像url数组
     */
    public static function CreateDefaultAvatar(string $name, $user_id = 'cache'): array
    {
        return ImageController::CreateUserDefaultAvatar($name, $user_id);
    }
}
