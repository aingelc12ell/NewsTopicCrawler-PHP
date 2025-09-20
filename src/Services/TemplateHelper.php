<?php
namespace App\Services;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TemplateHelper extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('timeago', [$this, 'timeAgo']),
            new TwigFilter('excerpt', [$this, 'excerpt']),
            new TwigFilter('domain', [$this, 'extractDomain']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [$this, 'asset']),
            new TwigFunction('url', [$this, 'url']),
        ];
    }

    public function timeAgo(string $datetime): string
    {
        $time = time() - strtotime($datetime);

        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';

        return floor($time/31536000) . ' years ago';
    }

    public function excerpt(string $text, int $length = 150): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }

    public function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? $url;
    }

    public function asset(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function url(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}