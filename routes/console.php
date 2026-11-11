<?php

use App\Support\UserContentCounts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('forum:recount-user-counts', function () {
    $totals = UserContentCounts::recount();

    foreach ($totals as $column => $count) {
        $this->line(sprintf('%-16s %d', $column, $count));
    }

    $this->info('用户内容计数已按内容表重算完成。');
})->purpose('按内容表重算 user 表的 topic/article/question/answer/comment/reply 计数');
