<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\HttpClient;

use Mj4444\SimpleHttpClient\HttpRequest\HttpMethod;

/**
 * HTTP-запрос для получения информации о загружаемом файле.
 *
 * @internal
 */
final readonly class ResumableInfoRequest extends BaseRequest
{
    /**
     * @param non-empty-string $url
     */
    public function __construct(
        private string $url
    ) {
    }

    public function getBody(): null
    {
        return null;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function getMethod(): string
    {
        return HttpMethod::Get->value;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isPost(): false
    {
        return false;
    }
}
