<?php declare(strict_types=1);

namespace App\Controllers;

use App\Services\Article\ShowArticleService;
use App\Services\Comments\CommentService;
use App\Services\User\RegistrationService;
use App\Services\User\UserService;
use App\Services\Article\IndexArticleService;
use App\Views\View;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Twig\Environment;

class HomeController
{
    private Client $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function index(): View
    {
        try {
            $service = new IndexArticleService($this->httpClient);
            return $service->index();
        } catch (GuzzleException $exception) {
            $errorMessage = 'Error fetching article data: ' . $exception->getMessage();
            return new View('Error', ['message' => $errorMessage]);
        }
    }

    public function show(Environment $twig, array $vars): View
    {
        $articleId = (int)$vars['id'];

        try {
            $service = new ShowArticleService($this->httpClient, $this);
            return $service->show($twig, $articleId);
        } catch (GuzzleException $exception) {
            $errorMessage = 'Error fetching article data: ' . $exception->getMessage();
            return new View('Error', ['message' => $errorMessage]);
        }
    }

    public function user(Environment $twig, array $vars): View
    {
        $userId = (int) $vars['id'];

        try {
            $commentService = new CommentService($this->httpClient, $twig);
            $userService = new UserService($this->httpClient, $commentService);

            $view = $userService->user($userId);

            return new View('User', $view);
        } catch (GuzzleException $exception) {
            $errorMessage = 'Error fetching article data: ' . $exception->getMessage();
            return new View('Error', ['message' => $errorMessage]);
        }
    }

    public function register(): View
    {
        try {
            $service = new RegistrationService($this->httpClient);
            return $service->index();
        } catch (GuzzleException $exception) {
            $errorMessage = 'Error fetching article data: ' . $exception->getMessage();
            return new View('Error', ['message' => $errorMessage]);
        }
    }

    public function login()
    {
        return new View('Login', ['message' => 'message']);
    }
}