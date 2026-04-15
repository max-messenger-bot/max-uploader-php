<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\MaxSimpleUploader;

use MaxMessenger\Uploader\Exceptions\SimpleUploaderException;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\StringFileInterface;

final readonly class StringFile implements StringFileInterface
{
    /**
     * @param non-empty-string $postName
     */
    public function __construct(
        public string $data,
        public string $postName
    ) {
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getMime(): string
    {
        return 'application/octet-stream';
    }

    public function getPostName(): string
    {
        return basename($this->postName)
            ?: throw new SimpleUploaderException('Failed to extract post name.');
    }
}
