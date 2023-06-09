<?php declare(strict_types=1);

namespace App\Services\Article;

use App\Cache;
use App\Repositories\IndexArticleRepository;
use App\Utils\RandomImage;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use GuzzleHttp\Client;
use Twig\Environment;
use App\Views\View;
use App\Services\Comments\CommentService;
use App\Controllers\HomeController;

class ShowArticleService
{
    private Client $httpClient;
    private HomeController $homeController;
    private IndexArticleRepository $articleRepository;

    public function __construct(Client $httpClient, HomeController $homeController)
    {
        $this->httpClient = $httpClient;
        $this->homeController = $homeController;
    }

    public function show(Environment $twig, int $articleId): View
    {
        try {
            $service = new IndexArticleService($this->httpClient);
            $articlesData = $service->index()->getData();
            $articles = $articlesData['articles'];
            $users = $articlesData['users'];

            $article = null;
            foreach ($articles as $item) {
                if ($item->getId() == $articleId) {
                    $article = $item;
                    break;
                }
            }

            $cacheKey = 'article_' . $articleId;
            if (Cache::has($cacheKey)) {
                $cachedArticle = Cache::get($cacheKey);
                $article = $cachedArticle;
            } else {
                $images = RandomImage::getRandomImages(1);
                $image = $images[0];

                $commentService = new CommentService($this->httpClient, $this->homeController);

                $commentsCacheKey = 'comments_' . $articleId;
                if (Cache::has($commentsCacheKey)) {
                    $comments = Cache::get($commentsCacheKey);
                } else {
                    $comments = $commentService->getComments($articleId, $articles, $users);

                    Cache::remember($commentsCacheKey, $comments, 20);
                }

                $viewData = [
                    'article' => $article,
                    'image' => $image,
                    'comments' => $comments,
                    'users' => $users
                ];
                Cache::remember($cacheKey, $article, 20);

                return new View('article', $viewData);
            }

            $images = RandomImage::getRandomImages(1);
            $image = $images[0];

            $commentService = new CommentService($this->httpClient, $this->homeController);
            $comments = $commentService->getComments($articleId, $articles, $users);

            return new View('article', [
                'article' => $article,
                'image' => $image,
                'comments' => $comments,
                'users' => $users
            ]);

        } catch (\Exception $exception) {
            $errorMessage = 'Error fetching article data: ' . $exception->getMessage();

            return new View('Error', ['message' => $errorMessage]);
        }
    }
}