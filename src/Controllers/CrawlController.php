<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Services\CrawlerService;
use App\Services\DuplicateChecker;

class CrawlController extends BaseController
{
    private CrawlerService $crawler;

    public function __construct()
    {
        parent::__construct();
        $this->crawler = new CrawlerService();
    }

    public function form(Request $request, Response $response): Response
    {
        $duplicateStats = DuplicateChecker::getDuplicateStats();

        $view = Twig::fromRequest($request);
        return $view->render($response, 'crawl/form.twig', [
            'duplicateStats' => $duplicateStats
        ]);
    }

    public function process(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $mode = $data['mode'] ?? 'general';
        $topic = trim($data['topic'] ?? '');
        $websites = $data['websites'] ?? '';

        if (empty($topic)) {
            $view = Twig::fromRequest($request);
            return $view->render($response->withStatus(400), 'crawl/error.twig', [
                'error' => 'Topic is required'
            ]);
        }

        try {
            $articles = [];

            if ($mode === 'general') {
                $articles = $this->crawler->crawlGeneral($topic);
            } else {
                $websiteList = array_filter(
                    array_map('trim', explode("\n", $websites)),
                    function($url) { return !empty($url); }
                );

                if (empty($websiteList)) {
                    $view = Twig::fromRequest($request);
                    return $view->render($response->withStatus(400), 'crawl/error.twig', [
                        'error' => 'At least one website URL is required for custom mode'
                    ]);
                }

                $articles = $this->crawler->crawlCustomWebsites($websiteList, $topic);
            }

            // Get detailed statistics including duplicates
            $stats = $this->crawler->getDetailedDuplicateStats();

            $view = Twig::fromRequest($request);
            return $view->render($response, 'crawl/results.twig', [
                'stats' => $stats,
                'topic' => $topic
            ]);

        } catch (\Exception $e) {
            $view = Twig::fromRequest($request);
            return $view->render($response->withStatus(500), 'crawl/error.twig', [
                'error' => 'Crawling failed: ' . $e->getMessage()
            ]);
        }
    }
    private function renderCrawlForm(array $duplicateStats): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Crawl Configuration</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link style="text/css" rel="stylesheet" href="/css/app.css" />
</head>
<body>
    <div class="form-container">
        <a href="/articles" class="back-link">← Back to Articles</a>
        <h1>🔍 Crawl Configuration</h1>
        
        <form method="POST" action="/crawl" id="crawlForm">
            <div class="form-group">
                <label>Crawling Mode</label>
                <div class="mode-selection">
                    <div class="mode-option selected" onclick="selectMode(\'general\')">
                        <input type="radio" name="mode" value="general" checked>
                        <div class="mode-title">General News Mode</div>
                        <div class="mode-description">Crawl popular news aggregators like Google News</div>
                    </div>
                    <div class="mode-option" onclick="selectMode(\'custom\')">
                        <input type="radio" name="mode" value="custom">
                        <div class="mode-title">Custom Websites Mode</div>
                        <div class="mode-description">Crawl specific websites you provide</div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="topic">Search Topic *</label>
                <input type="text" id="topic" name="topic" required 
                       placeholder="e.g., artificial intelligence, climate change, technology">
                <div class="help-text">Enter the topic you want to search for in news articles</div>
            </div>

            <div class="form-group websites-section" id="websitesSection">
                <label for="websites">Website URLs (one per line)</label>
                <textarea id="websites" name="websites" 
                         placeholder="https://example.com&#10;https://news.example.org&#10;techcrunch.com"></textarea>
                <div class="help-text">Enter website URLs, one per line. Protocol (https://) is optional.</div>
            </div>

            <div class="form-group">
                <button type="submit" class="submit-button">🚀 Start Crawling</button>
            </div>
        </form>
    </div>

    <script>
        function selectMode(mode) {
            // Update radio buttons
            document.querySelector(\'input[name="mode"][value="general"]\').checked = (mode === \'general\');
            document.querySelector(\'input[name="mode"][value="custom"]\').checked = (mode === \'custom\');
            
            // Update visual selection
            document.querySelectorAll(\'.mode-option\').forEach(option => {
                option.classList.remove(\'selected\');
            });
            event.currentTarget.classList.add(\'selected\');
            
            // Show/hide websites section
            const websitesSection = document.getElementById(\'websitesSection\');
            if (mode === \'custom\') {
                websitesSection.classList.add(\'show\');
                document.getElementById(\'websites\').required = true;
            } else {
                websitesSection.classList.remove(\'show\');
                document.getElementById(\'websites\').required = false;
            }
        }

        // Form validation
        document.getElementById(\'crawlForm\').addEventListener(\'submit\', function(e) {
            const topic = document.getElementById(\'topic\').value.trim();
            const mode = document.querySelector(\'input[name="mode"]:checked\').value;
            const websites = document.getElementById(\'websites\').value.trim();

            if (!topic) {
                alert(\'Please enter a search topic\');
                e.preventDefault();
                return;
            }

            if (mode === \'custom\' && !websites) {
                alert(\'Please enter at least one website URL for custom mode\');
                e.preventDefault();
                return;
            }

            // Show loading state
            const submitButton = document.querySelector(\'.submit-button\');
            submitButton.disabled = true;
            submitButton.textContent = \'🔄 Crawling in progress...\';
        });
    </script>
</body>
</html>';
    }

    private function renderCrawlResults(int $savedCount, int $totalFound, string $topic): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Crawl Results</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link style="text/css" rel="stylesheet" href="/css/app.css" />
</head>
<body>
    <div class="results-container">
        <div class="success-icon">🎉</div>
        <h1>Crawling Complete!</h1>
        
        <div class="stats">
            <div class="stat-item">
                <span class="stat-label">Topic:</span> 
                <span class="stat-value">' . htmlspecialchars($topic) . '</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Articles Found:</span> 
                <span class="stat-value">' . $totalFound . '</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Articles Saved:</span> 
                <span class="stat-value">' . $savedCount . '</span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="/articles" class="btn btn-primary">📰 View Articles</a>
            <a href="/crawl" class="btn btn-secondary">🔄 Crawl Again</a>
        </div>
    </div>
</body>
</html>';
    }

    private function renderError(Response $response, string $message): Response
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Crawl Error</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link style="text/css" rel="stylesheet" href="/css/app.css" />
</head>
<body>
    <div class="error-container">
        <div class="error-icon">❌</div>
        <h1>Crawling Failed</h1>
        
        <div class="error-message">
            ' . htmlspecialchars($message) . '
        </div>

        <div class="action-buttons">
            <a href="/crawl" class="btn btn-primary">🔄 Try Again</a>
            <a href="/articles" class="btn btn-secondary">📰 View Articles</a>
        </div>
    </div>
</body>
</html>';

        $response->getBody()->write($html);
        return $response;
    }
}