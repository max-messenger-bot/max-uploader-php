<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\MaxUploader\Content;

use Closure;
use MaxMessenger\Uploader\Contracts\ContentInterface;
use MaxMessenger\Uploader\Exceptions\StreamException;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\BodyInterface;
use Mj4444\SimpleHttpClient\HttpRequest\Body\StringStreamBody;

use function strlen;

final readonly class StringFile implements ContentInterface
{
    /**
     * @param string $content Строка содержимого.
     * @param non-empty-string $postName Имя файла, используемое при загрузке.
     */
    public function __construct(
        public string $content,
        public string $postName
    ) {
    }

    public function getBody(int $offset, int $length, ?Closure $progressCallback): BodyInterface
    {
        return new StringStreamBody($this->content, 'application/octet-stream', $offset, $length, $progressCallback);
    }

    public function getPostName(): string
    {
        return basename($this->postName) ?: throw new StreamException('Failed to extract post name.');
    }

    public function getSize(): int
    {
        return strlen($this->content);
    }
}
