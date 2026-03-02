<?php

namespace App\Service;

class YouTubeOEmbedService
{
    public function fetchPreview(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $endpoint = 'https://www.youtube.com/oembed?format=json&url=' . urlencode($url);
        $json = @file_get_contents($endpoint);
        if ($json !== false) {
            $data = json_decode($json, true);
            if (is_array($data)) {
                return [
                    'title' => (string) ($data['title'] ?? ''),
                    'author_name' => (string) ($data['author_name'] ?? ''),
                    'thumbnail_url' => (string) ($data['thumbnail_url'] ?? ''),
                    'provider_name' => (string) ($data['provider_name'] ?? 'YouTube'),
                ];
            }
        }

        $videoId = $this->extractVideoId($url);
        if ($videoId === null) {
            return null;
        }

        return [
            'title' => 'Video YouTube',
            'author_name' => '',
            'thumbnail_url' => 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg',
            'provider_name' => 'YouTube',
        ];
    }

    public function extractVideoId(string $url): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        if (($parts['host'] ?? '') === 'youtu.be') {
            $path = trim((string) ($parts['path'] ?? ''), '/');
            return $path !== '' ? $path : null;
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['v'])) {
                return (string) $query['v'];
            }
        }

        $path = (string) ($parts['path'] ?? '');
        if (preg_match('#/embed/([a-zA-Z0-9_-]{6,})#', $path, $m)) {
            return $m[1];
        }

        return null;
    }
}
