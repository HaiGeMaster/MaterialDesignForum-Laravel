<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $type = $request->query('type', 'all');

        $payload = [
            'users' => [],
            'topics' => [],
            'questions' => [],
            'articles' => [],
            'answers' => [],
            'comments' => [],
            'replies' => [],
        ];

        if ($keyword === '') {
            return response()->json($payload);
        }

        $types = $type === 'all'
            ? array_keys($payload)
            : [$this->normalizeType($type)];

        foreach ($types as $name) {
            $payload[$name] = match ($name) {
                'users' => $this->searchUsers($request, $keyword),
                'topics' => $this->searchTopics($request, $keyword),
                'questions' => $this->searchQuestions($request, $keyword),
                'articles' => $this->searchArticles($request, $keyword),
                'answers' => $this->searchAnswers($request, $keyword),
                'comments' => $this->searchComments($request, $keyword),
                'replies' => $this->searchReplies($request, $keyword),
                default => [],
            };
        }

        return response()->json($payload);
    }

    private function normalizeType(string $type): string
    {
        return match ($type) {
            'user', 'users' => 'users',
            'topic', 'topics' => 'topics',
            'question', 'questions' => 'questions',
            'article', 'articles' => 'articles',
            'answer', 'answers' => 'answers',
            'comment', 'comments' => 'comments',
            'reply', 'replies' => 'replies',
            default => 'users',
        };
    }

    private function searchUsers(Request $request, string $keyword): array
    {
        $items = User::query()
            ->whereNotDisabled()
            ->where(function ($query) use ($keyword) {
                $query->where('username', 'like', "%{$keyword}%")
                    ->orWhere('bio', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->limit(12)
            ->get();

        $this->attachUserCounts($items);
        $this->appendFollowStatus($items, $request, 'user', 'user_id');

        return $items->values()->all();
    }

    private function searchTopics(Request $request, string $keyword): array
    {
        $items = Topic::query()
            ->whereNull('delete_time')
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->limit(12)
            ->get();

        $this->appendAuthors($items, $request, 'user_id');
        $this->appendFollowStatus($items, $request, 'topic', 'topic_id');

        return $items->values()->all();
    }

    private function searchQuestions(Request $request, string $keyword): array
    {
        $items = Question::query()
            ->whereNull('delete_time')
            ->where(function ($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content_rendered', 'like', "%{$keyword}%")
                    ->orWhere('content_markdown', 'like', "%{$keyword}%");
            })
            ->limit(12)
            ->get();

        $this->appendAuthors($items, $request, 'user_id');
        $this->appendFollowStatus($items, $request, 'question', 'question_id');
        $this->attachContentTopicsToMany($items, 'question');

        return $items->values()->all();
    }

    private function searchArticles(Request $request, string $keyword): array
    {
        $items = Article::query()
            ->whereNull('delete_time')
            ->where(function ($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content_rendered', 'like', "%{$keyword}%")
                    ->orWhere('content_markdown', 'like', "%{$keyword}%");
            })
            ->limit(12)
            ->get();

        $this->appendAuthors($items, $request, 'user_id');
        $this->appendFollowStatus($items, $request, 'article', 'article_id');
        $this->attachContentTopicsToMany($items, 'article');

        return $items->values()->all();
    }

    private function searchAnswers(Request $request, string $keyword): array
    {
        $items = Answer::query()
            ->whereNull('delete_time')
            ->where(function ($query) use ($keyword) {
                $query->where('content_rendered', 'like', "%{$keyword}%")
                    ->orWhere('content_markdown', 'like', "%{$keyword}%");
            })
            ->limit(12)
            ->get();

        $this->appendAuthors($items, $request, 'user_id');
        $this->appendVoteStatus($items, $request, 'answer', 'answer_id');

        return $items->values()->all();
    }

    private function searchComments(Request $request, string $keyword): array
    {
        $items = Comment::query()
            ->whereNull('delete_time')
            ->where('content', 'like', "%{$keyword}%")
            ->limit(12)
            ->get();

        $this->appendAuthors($items, $request, 'user_id');
        $this->appendVoteStatus($items, $request, 'comment', 'comment_id');

        return $items->values()->all();
    }

    private function searchReplies(Request $request, string $keyword): array
    {
        $items = Reply::query()
            ->whereNull('delete_time')
            ->where('content', 'like', "%{$keyword}%")
            ->limit(12)
            ->get();

        $this->appendAuthors($items, $request, 'user_id');
        $this->appendVoteStatus($items, $request, 'reply', 'reply_id');

        return $items->values()->all();
    }
}
