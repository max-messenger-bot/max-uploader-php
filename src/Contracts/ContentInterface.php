<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\Contracts;

use Closure;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\BodyInterface;

interface ContentInterface
{
    /**
     * Возвращает `BodyInterface` для заданного аргументами $offset / $length фрагмента.
     *
     * Если загрузчик body поддерживает событие Progress, он должен вызывать замыкание $progressCallback.
     *
     * @param non-negative-int $offset
     * @param non-negative-int $length
     * @param Closure(non-negative-int $bytesSent, non-negative-int $totalBytes): void|null $progressCallback
     */
    public function getBody(int $offset, int $length, ?Closure $progressCallback): BodyInterface;

    /**
     * Возвращает имя файла, используемое при загрузке.
     *
     * @return non-empty-string
     */
    public function getPostName(): string;

    /**
     * Возвращает общий размер в байтах.
     *
     * @return non-negative-int
     */
    public function getSize(): int;
}
