<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Parsedown;

class ArticleController extends BaseController
{
    private string $articlesPath;

    public function __construct()
    {
        parent::__construct();
        $this->articlesPath = __DIR__ . '/../../storage/articles';
    }

    public function list(Request $request, Response $response): Response
    {
        $articles = $this->getArticlesList();

        $view = Twig::fromRequest($request);
        return $view->render($response, 'articles/list.twig', [
            'articles' => $articles,
            'isEmpty' => empty($articles)
        ]);
    }

    public function view(Request $request, Response $response, array $args): Response
    {
        $filename = $args['filename'];
        $filepath = $this->articlesPath . '/' . $filename;

        if (!file_exists($filepath) || !str_ends_with($filename, '.md')) {
            $view = Twig::fromRequest($request);
            return $view->render($response->withStatus(404), 'errors/404.twig', [
                'message' => 'Article not found'
            ]);
        }

        $content = file_get_contents($filepath);
        $parsedown = new Parsedown();
        $htmlContent = $parsedown->text($content);

        // Extract title from content
        preg_match('/^# (.+)$/m', $content, $titleMatches);
        $title = $titleMatches[1] ?? 'Article';

        $view = Twig::fromRequest($request);
        return $view->render($response, 'articles/view.twig', [
            'title' => $title,
            'content' => $htmlContent,
            'filename' => $filename
        ]);
    }

    private function getArticlesList(): array
    {
        if (!is_dir($this->articlesPath)) {
            return [];
        }

        $files = glob($this->articlesPath . '/*.md');
        $articles = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $content = file_get_contents($file);

            // Extract title (first line after #)
            preg_match('/^# (.+)$/m', $content, $titleMatches);
            $title = $titleMatches[1] ?? 'Untitled';

            // Extract source
            preg_match('/\*\*Source:\*\* \[([^\]]+)\]\(([^)]+)\)/', $content, $sourceMatches);
            $sourceDomain = $sourceMatches[1] ?? 'Unknown';
            $sourceLink = $sourceMatches[2] ?? '#';

            // Extract date
            preg_match('/\*\*Date Saved:\*\* (.+)$/m', $content, $dateMatches);
            $dateString = $dateMatches[1] ?? '';

            $articles[] = [
                'filename' => $filename,
                'title' => $title,
                'sourceDomain' => $sourceDomain,
                'sourceLink' => $sourceLink,
                'date' => $dateString,
                'timestamp' => filemtime($file)
            ];
        }

        // Sort by newest first
        usort($articles, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $articles;
    }
    private function renderArticlesList(array $articles): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>News Articles</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link style="text/css" rel="stylesheet" href="/css/app.css" />
</head>
<body>
    <div class="header">
        <h1>📰 News Articles</h1>
        <a href="/crawl" class="crawl-button">+ Crawl New Articles</a>
    </div>';

        if (empty($articles)) {
            $html .= '
    <div class="empty-state">
        <h2>No articles found</h2>
        <p>Start crawling to collect news articles!</p>
        <a href="/crawl" class="crawl-button">Start Crawling</a>
    </div>';
        } else {
            $html .= '<div class="articles-grid">';
            foreach ($articles as $article) {
                $html .= '
        <div class="article-card">
            <div class="article-title">
                <a href="/articles/' . htmlspecialchars($article['filename']) . '">' .
                         htmlspecialchars($article['title']) . '</a>
            </div>
            <div class="article-meta">
                <strong>Source:</strong> <a href="' . htmlspecialchars($article['sourceLink']) . '" 
                class="article-source" target="_blank">' . htmlspecialchars($article['sourceDomain']) . '</a><br>
                <strong>Saved:</strong> ' . htmlspecialchars($article['date']) . '
            </div>
        </div>';
            }
            $html .= '</div>';
        }

        $html .= '
</body>
</html>';

        return $html;
    }

    private function renderArticleView(string $content, string $filename): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Article - ' . htmlspecialchars($filename) . '</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link style="text/css" rel="stylesheet" href="/css/app.css" />
</head>
<body>
    <div class="article-container">
        <a href="/articles" class="back-link">← Back to Articles</a>
        ' . $content . '
    </div>
</body>
</html>';
    }
}