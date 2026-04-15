<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\MaxUploader\Content;

use Closure;
use MaxMessenger\Uploader\Contracts\ContentInterface;
use MaxMessenger\Uploader\Exceptions\StreamException;
use MaxMessenger\Uploader\Exceptions\StreamLogicException;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\BodyInterface;
use Mj4444\SimpleHttpClient\HttpRequest\Body\StreamBody;

use function is_int;
use function is_resource;

abstract readonly class BaseStream implements ContentInterface
{
    /**
     * @var non-negative-int
     */
    private int $size;

    /**
     * @param resource $resource Ресурс поддерживающий перемотку.
     * @param non-empty-string $postName Имя файла, используемое при загрузке.
     */
    public function __construct(
        public mixed $resource,
        public string $postName
    ) {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!is_resource($this->resource) || !$this->isSeekable()) {
            throw new StreamLogicException('A seekable stream is expected.');
        }

        $this->size = $this->extractResourceSize();
    }

    public function getBody(int $offset, int $length, ?Closure $progressCallback): BodyInterface
    {
        return new StreamBody($this->resource, 'application/octet-stream', $offset, $length, $progressCallback);
    }

    public function getPostName(): string
    {
        return basename($this->postName) ?: throw new StreamException('Failed to extract post name.');
    }

    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return non-negative-int
     */
    protected function extractResourceSize(): int
    {
        $stat = @fstat($this->resource);
        if ($stat === false || !is_int($size = $stat['size'] ?? null)) {
            if (@fseek($this->resource, 0, SEEK_END) !== 0) {
                throw new StreamException('Failed to get stream size (fseek).');
            }
            $size = @ftell($this->resource);
            if ($size === false) {
                throw new StreamException('Failed to get stream size (ftell).');
            }
        }

        if ($size < 0) {
            throw new StreamLogicException('Failed to get stream size ($size).');
        }

        return $size;
    }

    protected function isSeekable(): bool
    {
        $meta = @stream_get_meta_data($this->resource);

        return $meta['seekable'] ?? false;
    }
}
