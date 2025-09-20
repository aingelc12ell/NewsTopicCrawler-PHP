<?php
namespace App\Services;

use App\Models\Article;

class DuplicateChecker
{
    private static string $articlesPath = __DIR__ . '/../../storage/articles';
    private static string $indexPath = __DIR__ . '/../../storage/duplicate_index.json';

    public static function isDuplicate(Article $article): bool
    {
        // Load existing articles index
        $index = self::loadIndex();

        // Check for exact URL match
        if (self::hasUrlDuplicate($article, $index)) {
            return true;
        }

        // Check for content hash match
        if (self::hasContentHashDuplicate($article, $index)) {
            return true;
        }

        // Check for title similarity (high threshold)
        if (self::hasSimilarTitle($article, $index)) {
            return true;
        }

        // Check for content similarity
        if (self::hasSimilarContent($article)) {
            return true;
        }

        return false;
    }

    private static function hasUrlDuplicate(Article $article, array $index): bool
    {
        $normalizedUrl = self::normalizeUrl($article->sourceLink);

        foreach ($index as $existingArticle) {
            $existingUrl = self::normalizeUrl($existingArticle['sourceLink']);
            if ($normalizedUrl === $existingUrl) {
                return true;
            }
        }

        return false;
    }

    private static function hasContentHashDuplicate(Article $article, array $index): bool
    {
        foreach ($index as $existingArticle) {
            if ($article->contentHash === $existingArticle['contentHash']) {
                return true;
            }
        }

        return false;
    }

    private static function hasSimilarTitle(Article $article, array $index): bool
    {
        foreach ($index as $existingArticle) {
            // Check exact title hash match
            if ($article->titleHash === $existingArticle['titleHash']) {
                return true;
            }

            // Check Levenshtein distance for similar titles
            $similarity = self::calculateTitleSimilarity(
                $article->title,
                $existingArticle['title']
            );

            if ($similarity > 0.85) { // 85% similarity threshold
                return true;
            }
        }

        return false;
    }

    private static function hasSimilarContent(Article $article): bool
    {
        $existingFiles = glob(self::$articlesPath . '/*.md');

        foreach ($existingFiles as $file) {
            $content = file_get_contents($file);

            // Extract existing article content
            if (preg_match('/^# (.+)$/m', $content, $titleMatch) &&
                preg_match('/---\n\n(.+)$/s', $content, $contentMatch)) {

                $existingTitle = $titleMatch[1];
                $existingContent = $contentMatch[1];

                // Check content similarity
                $contentSimilarity = self::calculateContentSimilarity(
                    $article->summary,
                    $existingContent
                );

                if ($contentSimilarity > 0.80) { // 80% content similarity threshold
                    return true;
                }
            }
        }

        return false;
    }

    private static function calculateTitleSimilarity(string $title1, string $title2): float
    {
        $title1 = strtolower(trim($title1));
        $title2 = strtolower(trim($title2));

        // Use Levenshtein distance for similarity calculation
        $maxLen = max(strlen($title1), strlen($title2));
        if ($maxLen === 0) return 1.0;

        $distance = levenshtein($title1, $title2);
        return 1.0 - ($distance / $maxLen);
    }

    private static function calculateContentSimilarity(string $content1, string $content2): float
    {
        // Simple word-based similarity using Jaccard coefficient
        $words1 = array_unique(str_word_count(strtolower($content1), 1));
        $words2 = array_unique(str_word_count(strtolower($content2), 1));

        if (empty($words1) && empty($words2)) return 1.0;
        if (empty($words1) || empty($words2)) return 0.0;

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $intersection / $union;
    }

    public static function calculateSimilarity(Article $article1, Article $article2): float
    {
        $titleSim = self::calculateTitleSimilarity($article1->title, $article2->title);
        $contentSim = self::calculateContentSimilarity($article1->summary, $article2->summary);
        $urlSim = (self::normalizeUrl($article1->sourceLink) === self::normalizeUrl($article2->sourceLink)) ? 1.0 : 0.0;

        // Weighted average: title 40%, content 40%, URL 20%
        return ($titleSim * 0.4) + ($contentSim * 0.4) + ($urlSim * 0.2);
    }

    private static function normalizeUrl(string $url): string
    {
        // Remove protocol, www, trailing slashes, and common parameters
        $url = strtolower($url);
        $url = preg_replace('/^https?:\/\//', '', $url);
        $url = preg_replace('/^www\./', '', $url);
        $url = rtrim($url, '/');

        // Remove common tracking parameters
        $url = preg_replace('/[?&](utm_|fbclid|gclid|ref=|source=).*$/', '', $url);

        return $url;
    }

    public static function addToIndex(Article $article): void
    {
        $index = self::loadIndex();

        $index[] = [
            'filename' => $article->filename,
            'title' => $article->title,
            'sourceLink' => $article->sourceLink,
            'sourceDomain' => $article->sourceDomain,
            'contentHash' => $article->contentHash,
            'titleHash' => $article->titleHash,
            'savedDate' => $article->savedDate->format('Y-m-d H:i:s')
        ];

        self::saveIndex($index);
    }

    public static function removeFromIndex(string $filename): void
    {
        $index = self::loadIndex();

        $index = array_filter($index, function($item) use ($filename) {
            return $item['filename'] !== $filename;
        });

        self::saveIndex(array_values($index));
    }

    private static function loadIndex(): array
    {
        if (!file_exists(self::$indexPath)) {
            return [];
        }

        $content = file_get_contents(self::$indexPath);
        $index = json_decode($content, true);

        return is_array($index) ? $index : [];
    }

    private static function saveIndex(array $index): void
    {
        $directory = dirname(self::$indexPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(self::$indexPath, json_encode($index, JSON_PRETTY_PRINT));
    }

    public static function cleanupIndex(): void
    {
        $index = self::loadIndex();
        $existingFiles = glob(self::$articlesPath . '/*.md');
        $existingFilenames = array_map('basename', $existingFiles);

        // Remove index entries for files that no longer exist
        $cleanIndex = array_filter($index, function($item) use ($existingFilenames) {
            return in_array($item['filename'], $existingFilenames);
        });

        self::saveIndex(array_values($cleanIndex));
    }

    public static function getDuplicateStats(): array
    {
        $index = self::loadIndex();
        $total = count($index);

        // Group by domain to show duplicate sources
        $domains = [];
        $titleHashes = [];
        $contentHashes = [];

        foreach ($index as $item) {
            $domains[$item['sourceDomain']] = ($domains[$item['sourceDomain']] ?? 0) + 1;
            $titleHashes[$item['titleHash']] = ($titleHashes[$item['titleHash']] ?? 0) + 1;
            $contentHashes[$item['contentHash']] = ($contentHashes[$item['contentHash']] ?? 0) + 1;
        }

        return [
            'total_articles' => $total,
            'domains' => $domains,
            'potential_title_duplicates' => count(array_filter($titleHashes, fn($count) => $count > 1)),
            'potential_content_duplicates' => count(array_filter($contentHashes, fn($count) => $count > 1))
        ];
    }
}