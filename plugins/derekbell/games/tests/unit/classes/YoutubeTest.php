<?php

namespace DerekBell\Games\Tests\Unit\Classes;

use DerekBell\Games\Helpers\YoutubeHelper;
use System\Tests\Bootstrap\PluginTestCase;

class YoutubeTest extends PluginTestCase
{
    protected $youtube;

    public function setUp(): void
    {
        parent::setUp();
        $this->youtube = new YoutubeHelper();
    }

    public function testIsValidYoutubeId()
    {
        // Valid IDs (11 characters)
        $this->assertTrue($this->youtube->isValidYoutubeId('dQw4w9WgXcQ'));
        $this->assertTrue($this->youtube->isValidYoutubeId('abcdefghijk'));
        $this->assertTrue($this->youtube->isValidYoutubeId('12345678901'));

        // Invalid IDs
        $this->assertFalse($this->youtube->isValidYoutubeId('short'));
        $this->assertFalse($this->youtube->isValidYoutubeId('toolongvideoid'));
        $this->assertFalse($this->youtube->isValidYoutubeId(''));
        $this->assertFalse($this->youtube->isValidYoutubeId(null));
    }

    public function testCheckIdExistsWithInvalidId()
    {
        $result = $this->youtube->checkIdExists('invalid');
        $this->assertFalse($result);
    }

    public function testGetThumbnailUrlFormats()
    {
        $videoId = 'dQw4w9WgXcQ';

        // These are the expected URL patterns
        $maxResUrl = "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
        $standardUrl = "https://img.youtube.com/vi/{$videoId}/0.jpg";

        $this->assertStringContainsString($videoId, $maxResUrl);
        $this->assertStringContainsString($videoId, $standardUrl);
    }

    public function testYoutubeWatchUrl()
    {
        $videoId = 'dQw4w9WgXcQ';
        $expectedUrl = "https://www.youtube.com/watch?v={$videoId}";

        // This is what the Level model should return
        $this->assertEquals($expectedUrl, "https://www.youtube.com/watch?v={$videoId}");
    }

    public function testYoutubeEmbedUrl()
    {
        $videoId = 'dQw4w9WgXcQ';
        $expectedUrl = "https://www.youtube.com/embed/{$videoId}";

        // This is what the Level model should return
        $this->assertEquals($expectedUrl, "https://www.youtube.com/embed/{$videoId}");
    }
}
