<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\HttpClient;

use Mj4444\SimpleHttpClient\Contracts\HttpRequest\FileInterface;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\StringFileInterface;
use Mj4444\SimpleHttpClient\Contracts\HttpRequestExInterface;
use Mj4444\SimpleHttpClient\HttpRequest\Body\MultipartFormBody;
use Mj4444\SimpleHttpClient\HttpRequest\HttpMethod;

/**
 * HTTP-запрос для простой загрузки файла.
 *
 * @internal
 */
final readonly class SimpleUploadRequest extends BaseRequest implements HttpRequestExInterface
{
    /**
     * @param non-empty-string $url
     * @param positive-int $lowSpeedLimit
     * @param positive-int $lowSpeedTime
     */
    public function __construct(
        private string $url,
        private FileInterface|StringFileInterface $file,
        private int $lowSpeedLimit,
        private int $lowSpeedTime,
        private bool $isJsonResponse
    ) {
    }

    public function getBody(): MultipartFormBody
    {
        return new MultipartFormBody(['data' => $this->file]);
    }

    public function getHeaders(): array
    {
        return $this->isJsonResponse
            ? ['Accept: application/json; charset=utf-8']
            : [];
    }

    public function getLowSpeedLimit(): int
    {
        return $this->lowSpeedLimit;
    }

    public function getLowSpeedTime(): int
    {
        return $this->lowSpeedTime;
    }

    public function getMethod(): string
    {
        return HttpMethod::Post->value;
    }

    public function getProgressCallback(): null
    {
        return null;
    }

    public function getResourceForResponseBody(): null
    {
        return null;
    }

    public function getResumeFrom(): null
    {
        return null;
    }

    public function getTimeout(): int
    {
        return 0;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getWriteFunction(): null
    {
        return null;
    }

    public function isPost(): true
    {
        return true;
    }
}
