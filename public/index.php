<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Controllers\HomeController;
use App\Controllers\ArticleController;
use App\Controllers\CrawlController;
use App\Services\TemplateHelper;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Create Twig with cache directory
$twig = Twig::create(__DIR__ . '/../templates', [
    'cache' => __DIR__ . '/../cache/twig',
    'auto_reload' => true,
    'debug' => true
]);

// Add custom template helper
$twig->getEnvironment()->addExtension(new TemplateHelper());

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Routes
$app->get('/', [HomeController::class, 'index']);
$app->get('/articles', [ArticleController::class, 'list']);
$app->get('/articles/{filename}', [ArticleController::class, 'view']);
$app->get('/crawl', [CrawlController::class, 'form']);
$app->post('/crawl', [CrawlController::class, 'process']);

$app->run();