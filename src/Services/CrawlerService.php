<?php

namespace App\Services;

use App\Models\Article;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerService
{
    private Client          $httpClient;
    private LoggerInterface $logger;
    private array           $userAgents     = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    ];
    private array           $duplicateStats = [];

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify'  => false,
            'headers' => [
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection'      => 'keep-alive',
            ],
        ]);

        $this->logger = new Logger('crawler');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/crawler.log', Logger::INFO));

        // Initialize duplicate stats
        $this->duplicateStats = [
            'found'              => 0,
            'duplicates_skipped' => 0,
            'url_duplicates'     => 0,
            'title_duplicates'   => 0,
            'content_duplicates' => 0,
            'saved'              => 0,
        ];
    }

    public function crawlGeneral(string $topic): array
    {
        $articles = [];

        // Clean up duplicate index before crawling
        DuplicateChecker::cleanupIndex();

        // Google News
        $googleNewsArticles = $this->crawlGoogleNews($topic);
        $articles = array_merge($articles, $googleNewsArticles);

        // Add delay between different sources
        sleep(2);

        return $this->processArticlesWithDuplicateCheck($articles, $topic);
    }

    private function crawlGoogleNews(string $topic): array
    {
        $articles = [];
        $encodedTopic = urlencode($topic);
        $url = "https://news.google.com/search?q={$encodedTopic}&hl=en-US&gl=US&ceid=US:en";

        try {
            $response = $this->makeRequest($url);
            $crawler = new Crawler($response);

            // Google News uses specific selectors
            $crawler->filter('article')->each(function (Crawler $node) use (&$articles, $topic) {
                try {
                    $titleElement = $node->filter('h3 a');
                    if ($titleElement->count() > 0) {
                        $title = $titleElement->text();
                        $relativeLink = $titleElement->attr('href');

                        // Convert relative URL to absolute
                        $link = $this->resolveUrl('https://news.google.com', $relativeLink);

                        // Try to get summary from snippet
                        $summary = '';
                        $summaryElement = $node->filter('p');
                        if ($summaryElement->count() > 0) {
                            $summary = $summaryElement->text();
                        }

                        if (empty($summary)) {
                            $summary = "Article about: {$topic}";
                        }

                        $domain = parse_url($link, PHP_URL_HOST) ?? 'news.google.com';

                        $articles[] = new Article($title, $link, $summary, $domain);
                    }
                } catch (\Exception $e) {
                    $this->logger->warning("Error parsing Google News article: " . $e->getMessage());
                }
            });
        } catch (\Exception $e) {
            $this->logger->error("Failed to crawl Google News: " . $e->getMessage());
        }

        return $articles;
    }

    private function makeRequest(string $url): string
    {
        $userAgent = $this->userAgents[array_rand($this->userAgents)];

        $response = $this->httpClient->get($url, [
            'headers' => ['User-Agent' => $userAgent],
        ]);

        return $response->getBody()->getContents();
    }

    private function resolveUrl(string $base, string $relative): string
    {
        if (preg_match('/^https?:\/\//', $relative)) {
            return $relative;
        }

        if (strpos($relative, '//') === 0) {
            return 'https:' . $relative;
        }

        if (strpos($relative, '/') === 0) {
            $parsed = parse_url($base);
            return $parsed['scheme'] . '://' . $parsed['host'] . $relative;
        }

        return rtrim($base, '/') . '/' . $relative;
    }

    private function processArticlesWithDuplicateCheck(array $articles, string $topic): array
    {
        $this->duplicateStats['found'] = count($articles);

        // Filter and rank articles first
        $filteredArticles = $this->filterAndRankArticles($articles, $topic);

        // Check for duplicates and save valid articles
        $validArticles = [];

        foreach ($filteredArticles as $article) {
            if ($article->isDuplicate()) {
                $this->duplicateStats['duplicates_skipped']++;
                $this->logger->info("Skipped duplicate article: {$article->title}");
                continue;
            }

            // Save article and add to index
            if ($article->save()) {
                DuplicateChecker::addToIndex($article);
                $validArticles[] = $article;
                $this->duplicateStats['saved']++;
                $this->logger->info("Saved new article: {$article->title}");
            }
        }

        $this->logger->info("Duplicate check summary: " . json_encode($this->duplicateStats));

        return $validArticles;
    }

    private function filterAndRankArticles(array $articles, string $topic): array
    {
        // Remove duplicates based on title similarity
        $filtered = [];
        $seenTitles = [];

        foreach ($articles as $article) {
            $titleKey = strtolower(preg_replace('/[^a-z0-9]/', '', $article->title));
            if (!isset($seenTitles[$titleKey])) {
                $seenTitles[$titleKey] = true;
                $filtered[] = $article;
            }
        }

        // Sort by relevance (simple keyword matching)
        usort($filtered, function ($a, $b) use ($topic) {
            $scoreA = $this->calculateRelevanceScore($a->title, $topic);
            $scoreB = $this->calculateRelevanceScore($b->title, $topic);
            return $scoreB - $scoreA;
        });

        return array_slice($filtered, 0, 20); // Limit to top 20 articles
    }

    private function calculateRelevanceScore(string $title, string $topic): int
    {
        $titleLower = strtolower($title);
        $topicLower = strtolower($topic);
        $keywords = explode(' ', $topicLower);

        $score = 0;
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2) {
                $score += substr_count($titleLower, $keyword) * strlen($keyword);
            }
        }

        return $score;
    }

    public function crawlCustomWebsites(array $websites, string $topic): array
    {
        $articles = [];

        // Clean up duplicate index before crawling
        DuplicateChecker::cleanupIndex();

        foreach ($websites as $website) {
            try {
                $websiteArticles = $this->crawlWebsite($website, $topic);
                $articles = array_merge($articles, $websiteArticles);

                // Rate limiting - delay between websites
                sleep(rand(2, 5));
            } catch (\Exception $e) {
                $this->logger->error("Failed to crawl {$website}: " . $e->getMessage());
            }
        }

        return $this->processArticlesWithDuplicateCheck($articles, $topic);
    }

    private function crawlWebsite(string $website, string $topic): array
    {
        $articles = [];
        $url = $this->normalizeUrl($website);

        try {
            $response = $this->makeRequest($url);
            $crawler = new Crawler($response);
            $domain = parse_url($url, PHP_URL_HOST);

            // Common article selectors
            $selectors = [
                'article h1 a, article h2 a, article h3 a',
                '.post-title a, .entry-title a',
                'h1 a, h2 a, h3 a',
                '.news-item a, .article-item a',
                'a[href*="/article/"], a[href*="/news/"], a[href*="/post/"]',
            ];

            foreach ($selectors as $selector) {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$articles, $topic, $url, $domain) {
                    try {
                        $title = trim($node->text());
                        $link = $this->resolveUrl($url, $node->attr('href'));

                        // Basic relevance check
                        if ($this->isRelevant($title, $topic)) {
                            // Try to get article summary
                            $summary = $this->getArticleSummary($link);
                            if (empty($summary)) {
                                $summary = "Article from {$domain} about: {$topic}";
                            }

                            $articles[] = new Article($title, $link, $summary, $domain);
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning("Error parsing article from {$domain}: " . $e->getMessage());
                    }
                });

                // If we found articles with this selector, break
                if (!empty($articles)) {
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed to crawl {$website}: " . $e->getMessage());
        }

        return $articles;
    }

    private function normalizeUrl(string $url): string
    {
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    private function isRelevant(string $title, string $topic): bool
    {
        $titleLower = strtolower($title);
        $topicLower = strtolower($topic);
        $keywords = explode(' ', $topicLower);

        $relevanceScore = 0;
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2 && strpos($titleLower, $keyword) !== false) {
                $relevanceScore++;
            }
        }

        return $relevanceScore > 0;
    }

    private function getArticleSummary(string $url): string
    {
        try {
            $response = $this->makeRequest($url);
            $crawler = new Crawler($response);

            // Try different selectors for article content
            $contentSelectors = [
                '.entry-content p:first-of-type',
                '.post-content p:first-of-type',
                'article p:first-of-type',
                '.content p:first-of-type',
                'p:first-of-type',
            ];

            foreach ($contentSelectors as $selector) {
                $element = $crawler->filter($selector);
                if ($element->count() > 0) {
                    $text = trim($element->text());
                    if (strlen($text) > 50) {
                        return substr($text, 0, 300) . '...';
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning("Could not get summary for {$url}: " . $e->getMessage());
        }

        return '';
    }

    public function getDetailedDuplicateStats(): array
    {
        return array_merge(
            $this->duplicateStats,
            DuplicateChecker::getDuplicateStats()
        );
    }

    public function getDuplicateStats(): array
    {
        return $this->duplicateStats;
    }
}