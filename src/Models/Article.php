<?php
namespace App\Models;

use App\Services\DuplicateChecker;

class Article
{
    public string $title;
    public string $sourceLink;
    public string $summary;
    public string $sourceDomain;
    public string $filename;
    public \DateTime $savedDate;
    public string $contentHash;
    public string $titleHash;

    public function __construct(
        string $title,
        string $sourceLink,
        string $summary,
        string $sourceDomain
    ) {
        $this->title = $title;
        $this->sourceLink = $sourceLink;
        $this->summary = $summary;
        $this->sourceDomain = $sourceDomain;
        $this->savedDate = new \DateTime();
        $this->generateHashes();
        $this->filename = $this->generateFilename();
    }

    private function generateHashes(): void
    {
        // Generate content hash from title + summary for duplicate detection
        $this->contentHash = hash('sha256', strtolower(trim($this->title . ' ' . $this->summary)));

        // Generate title hash for similar title detection
        $normalizedTitle = $this->normalizeTitle($this->title);
        $this->titleHash = hash('sha256', $normalizedTitle);
    }

    private function normalizeTitle(string $title): string
    {
        // Remove common words and normalize for better duplicate detection
        $title = strtolower(trim($title));

        // Remove punctuation and special characters
        $title = preg_replace('/[^\w\s]/', ' ', $title);

        // Remove common stop words
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
            'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those'
        ];

        $words = explode(' ', $title);
        $filteredWords = array_filter($words, function($word) use ($stopWords) {
            return !in_array(trim($word), $stopWords) && strlen(trim($word)) > 2;
        });

        return implode(' ', array_values($filteredWords));
    }

    private function generateFilename(): string
    {
        $slug = $this->createSlug($this->title);
        $date = $this->savedDate->format('Y-m-d');
        $baseFilename = "{$date}-{$slug}.md";

        $counter = 1;
        $filename = $baseFilename;

        while (file_exists(__DIR__ . "/../../storage/articles/{$filename}")) {
            $filename = "{$date}-{$slug}-{$counter}.md";
            $counter++;
        }

        return $filename;
    }

    private function createSlug(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return substr(trim($slug, '-'), 0, 50);
    }

    public function toMarkdown(): string
    {
        $content = "# {$this->title}\n\n";
        $content .= "**Source:** [{$this->sourceDomain}]({$this->sourceLink})\n";
        $content .= "**Date Saved:** {$this->savedDate->format('Y-m-d H:i:s')}\n";
        $content .= "**Content Hash:** {$this->contentHash}\n";
        $content .= "**Title Hash:** {$this->titleHash}\n\n";
        $content .= "---\n\n";
        $content .= $this->summary;

        return $content;
    }

    public function save(): bool
    {
        $directory = __DIR__ . '/../../storage/articles';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = "{$directory}/{$this->filename}";
        return file_put_contents($filepath, $this->toMarkdown()) !== false;
    }

    public function isDuplicate(): bool
    {
        return DuplicateChecker::isDuplicate($this);
    }

    public function calculateSimilarity(Article $other): float
    {
        return DuplicateChecker::calculateSimilarity($this, $other);
    }
}