<?php

namespace DerekBell\Games\Helpers;

use DerekBell\Games\Models\Level;
use GuzzleHttp\Client;

class YoutubeHelper
{
    /**
     * Valid characters for YouTube ID.
     */
    const VALID_CHARS = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
        'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
        'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
        'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z', '-', '_'
    ];

    /**
     * Extract video ID from YouTube URL
     */
    public function extractVideoId($url)
    {
        if (!$url) {
            return null;
        }

        // If already looks like an ID
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }

        $patterns = [
            '/[?&]v=([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract playlist ID from YouTube URL
     */
    public function extractPlaylistId($url)
    {
        if (!$url) {
            return null;
        }

        // Try to parse list= from query string first
        $parts = @parse_url($url);
        if (!empty($parts['query'])) {
            $query = [];
            parse_str($parts['query'], $query);
            if (!empty($query['list'])) {
                return $query['list'];
            }
        }

        // Handle various YouTube playlist URL formats
        $patterns = [
            '/[?&]list=([a-zA-Z0-9_-]+)/',  // Standard format
            '/playlist\?list=([a-zA-Z0-9_-]+)/',  // Direct playlist URL
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        // If no query parameter, assume it's the playlist ID itself
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $url)) {
            return $url;
        }

        return null;
    }

    /**
     * Returns a configured Guzzle HTTP client.
     * Override this method in tests to inject a mock client.
     */
    protected function getHttpClient(array $config = [])
    {
        return new Client(array_merge(['timeout' => 10], $config));
    }

    /**
     * Fetch playlist items from YouTube Data API
     */
    public function fetchPlaylistItems($playlistId, $apiKey = null)
    {
        if (!$apiKey) {
            $apiKey = config('services.youtube.api_key');
        }

        if (!$apiKey) {
            return $this->fetchPlaylistItemsFromFeed($playlistId);
        }

        $items = [];
        $nextPageToken = null;

        do {
            $url = 'https://www.googleapis.com/youtube/v3/playlistItems';
            $params = [
                'part' => 'snippet,contentDetails',
                'playlistId' => $playlistId,
                'maxResults' => 50,
                'key' => $apiKey
            ];

            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }

            $url .= '?' . http_build_query($params);

            try {
                $httpResponse = $this->getHttpClient()->get($url, ['http_errors' => false]);
                $data = json_decode((string) $httpResponse->getBody(), true);

                if (isset($data['error'])) {
                    throw new \Exception('YouTube API Error: ' . ($data['error']['message'] ?? 'Unknown error'));
                }

                if (isset($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $items[] = [
                            'video_id' => $item['contentDetails']['videoId'] ?? null,
                            'title' => $item['snippet']['title'] ?? 'Untitled',
                            'description' => $item['snippet']['description'] ?? '',
                            'position' => $item['snippet']['position'] ?? 0,
                            'thumbnail_url' => $item['snippet']['thumbnails']['high']['url'] ?? null,
                        ];
                    }
                }

                $nextPageToken = $data['nextPageToken'] ?? null;

            } catch (\Exception $e) {
                \Log::error('YouTube API request failed: ' . $e->getMessage());
                throw $e;
            }

        } while ($nextPageToken);

        return $items;
    }

    /**
     * Fetch playlist items from the public Atom feed (no API key required)
     */
    public function fetchPlaylistItemsFromFeed($playlistId)
    {
        $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . urlencode($playlistId);

        try {
            $feedContent = (string) $this->getHttpClient()->get($url)->getBody();
            $xml = simplexml_load_string($feedContent);

            if ($xml === false) {
                throw new \Exception('Failed to parse playlist feed from YouTube');
            }

            $namespaces = $xml->getNamespaces(true);
            $items = [];
            $position = 0;

            foreach ($xml->entry as $entry) {
                $yt = isset($namespaces['yt']) ? $entry->children($namespaces['yt']) : null;
                $media = isset($namespaces['media']) ? $entry->children($namespaces['media']) : null;
                $videoId = $yt ? (string) $yt->videoId : null;
                $title = (string) $entry->title;
                $description = $media && $media->group ? (string) $media->group->description : '';
                $thumbnailUrl = null;

                if ($media && $media->group && $media->group->thumbnail) {
                    $thumbnailUrl = (string) $media->group->thumbnail->attributes()->url;
                }

                $items[] = [
                    'video_id' => $videoId,
                    'title' => $title ?: 'Untitled',
                    'description' => $description,
                    'position' => $position,
                    'thumbnail_url' => $thumbnailUrl,
                ];

                $position++;
            }

            return $items;
        } catch (\Exception $e) {
            \Log::error('YouTube feed request failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch playlist title from YouTube Data API
     */
    public function fetchPlaylistTitle($playlistId, $apiKey = null)
    {
        if (!$apiKey) {
            $apiKey = config('services.youtube.api_key');
        }

        if (!$apiKey) {
            return $this->fetchPlaylistTitleFromFeed($playlistId);
        }

        $url = 'https://www.googleapis.com/youtube/v3/playlists';
        $params = [
            'part' => 'snippet',
            'id' => $playlistId,
            'maxResults' => 1,
            'key' => $apiKey
        ];

        $url .= '?' . http_build_query($params);

        try {
            $httpResponse = $this->getHttpClient()->get($url, ['http_errors' => false]);
            $data = json_decode((string) $httpResponse->getBody(), true);

            if (isset($data['error'])) {
                throw new \Exception('YouTube API Error: ' . ($data['error']['message'] ?? 'Unknown error'));
            }

            if (!empty($data['items'][0]['snippet']['title'])) {
                return $data['items'][0]['snippet']['title'];
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('YouTube API request failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch playlist title from the public Atom feed (no API key required)
     */
    public function fetchPlaylistTitleFromFeed($playlistId)
    {
        $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . urlencode($playlistId);

        try {
            $feedContent = (string) $this->getHttpClient()->get($url)->getBody();
            $xml = simplexml_load_string($feedContent);

            if ($xml === false) {
                throw new \Exception('Failed to parse playlist feed from YouTube');
            }

            $title = (string) $xml->title;
            return $title ?: null;
        } catch (\Exception $e) {
            \Log::error('YouTube feed request failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function isValidYoutubeId($id)
    {
        // Check if empty or not a string
        if (empty($id) || !is_string($id)) {
            return false;
        }

        // Check length
        if (strlen($id) !== 11) {
            return false;
        }

        // Use regex for better performance
        return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1;
    }

    public function checkIdExists($id)
    {
        if (!$this->isValidYoutubeId($id)) {
            return false;
        }

        $url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=$id&format=json";

        try {
            $response = $this->getHttpClient(['timeout' => 5])->get($url, ['http_errors' => false]);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $json = json_decode((string) $response->getBody());

            return $json !== null && isset($json->title);
        } catch (\Exception $e) {
            \Log::error('YouTube ID check failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getThumbnailFile($youtube_thumbnail_id)
    {
        if (empty($youtube_thumbnail_id) || !$this->isValidYoutubeId($youtube_thumbnail_id)) {
            \Log::warning('YoutubeHelper: Skipping thumbnail fetch, missing or invalid YouTube ID.');
            return null;
        }

        // get the thumbnail file (try maxresdefault first, fallback to 0.jpg)
        $url = "https://img.youtube.com/vi/$youtube_thumbnail_id/maxresdefault.jpg";

        try {
            $file = new \System\Models\File;
            $file->fromUrl($url);
            return $file;
        } catch (\Exception $e) {
            // Fallback to standard thumbnail
            $url = "https://img.youtube.com/vi/$youtube_thumbnail_id/0.jpg";
            try {
                $file = new \System\Models\File;
                $file->fromUrl($url);
                return $file;
            } catch (\Exception $e2) {
                \Log::warning('YoutubeHelper: Could not fetch thumbnail for YouTube ID ' . $youtube_thumbnail_id . ': ' . $e2->getMessage());
                return null;
            }
        }
    }

    public function putFileInStorage($file, $youtube_thumbnail_id)
    {
        $path = storage_path('youtube/' . $youtube_thumbnail_id . '.jpg');
        $result = file_put_contents($path, $file);

        if ($result === false) {
            throw new \Exception("Failed to write file to storage.");
        }
    }

    /**
     * Attach a YouTube thumbnail to a level.
     *
     * @param int $id The ID of the level.
     * @param \System\Models\File $file The file to attach.
     * @return bool True if the thumbnail was attached successfully, false otherwise.
     */
    public function attachYoutubeThumbnail($id, $file)
    {
        if (!$file) {
            return false;
        }

        // get the level
        $level = Level::find($id);

        if ($level === null) {
            \Log::warning("Level with ID $id not found for thumbnail attachment");
            return false;
        }

        try {
            // attach the file
            $level->youtube_thumbnail()->add($file);
            $level->save();
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to attach YouTube thumbnail: ' . $e->getMessage());
            return false;
        }
    }

    public function attachYoutubeThumbnailFromId($id)
    {
        $level = Level::find($id);

        if ($level === null) {
            \Log::warning("attachYoutubeThumbnailFromId: level {$id} not found");
            return;
        }

        if (empty($level->youtube_id) || $level->youtube_thumbnail !== null) {
            return;
        }

        $file = $this->getThumbnailFile($level->youtube_id);
        if ($file) {
            $this->attachYoutubeThumbnail($level->id, $file);
        } else {
            \Log::info('YoutubeHelper: No thumbnail attached for level ' . $level->id . ' due to missing or invalid YouTube ID.');
        }
    }
}
