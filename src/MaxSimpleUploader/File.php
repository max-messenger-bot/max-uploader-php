<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\MaxSimpleUploader;

use MaxMessenger\Uploader\Exceptions\SimpleUploaderException;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\FileInterface;

final readonly class File implements FileInterface
{
    /**
     * @param non-empty-string $fileName
     * @param non-empty-string|null $postName
     */
    public function __construct(
        public string $fileName,
        public ?string $postName = null,
    ) {
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getMime(): string
    {
        return 'application/octet-stream';
    }

    public function getPostName(): string
    {
        return basename($this->postName ?? $this->fileName)
            ?: throw new SimpleUploaderException('Failed to extract post name.');
    }
}
