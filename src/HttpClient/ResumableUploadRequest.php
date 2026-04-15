<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\HttpClient;

use Closure;
use MaxMessenger\Uploader\Contracts\ContentInterface;
use MaxMessenger\Uploader\Exceptions\StreamLogicException;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\BodyInterface;
use Mj4444\SimpleHttpClient\Contracts\HttpRequestExInterface;
use Mj4444\SimpleHttpClient\HttpRequest\HttpMethod;

use function sprintf;

/**
 * HTTP-запрос для возобновляемой загрузки файла.
 *
 * @internal
 */
final readonly class ResumableUploadRequest extends BaseRequest implements HttpRequestExInterface
{
    private int $expectedHttpCode;
    private int $size;

    /**
     * @param non-empty-string $url
     * @param ContentInterface $content
     * @param non-negative-int $offset
     * @param positive-int $length
     * @param non-negative-int $timeout
     * @param positive-int $lowSpeedLimit
     * @param positive-int $lowSpeedTime
     * @param Closure(non-negative-int $bytesSent, non-negative-int $totalBytes): void|null $progressCallback
     * @param bool $expectedJsonResponse
     */
    public function __construct(
        private string $url,
        private ContentInterface $content,
        private int $offset,
        private int $length,
        private int $timeout,
        private int $lowSpeedLimit,
        private int $lowSpeedTime,
        private ?Closure $progressCallback,
        private bool $expectedJsonResponse
    ) {
        $this->size = $this->content->getSize();
        $this->expectedHttpCode = $this->offset + $this->length >= $this->size ? 200 : 201;

        if ($this->size < 1) {
            throw new StreamLogicException('The content size cannot be less than 1.');
        }
        /** @psalm-suppress DocblockTypeContradiction */
        if ($length < 1) {
            throw new StreamLogicException('The fragment length cannot be less than 1.');
        }
    }

    public function getBody(): BodyInterface
    {
        return $this->content->getBody($this->offset, $this->length, $this->progressCallback);
    }

    public function getHeaders(): array
    {
        $fileName = rawurlencode($this->content->getPostName());
        $contentDisposition = sprintf('attachment; filename=%s', $fileName);
        $range = sprintf('%d-%d/%d', $this->offset, $this->offset + $this->length - 1, $this->size);

        $headers = [
            'Content-Disposition: ' . $contentDisposition,
            'Content-Length: ' . $this->length,
            'Content-Range: bytes ' . $range,
        ];

        if ($this->expectedJsonResponse) {
            $headers[] = 'Accept: application/json; charset=utf-8';
        }

        return $headers;
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
        return max($this->timeout, 0);
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

    public function makeResponse(
        int $httpCode,
        string $url,
        string $effectiveUrl,
        ?string $redirectUrl,
        array $headers,
        ?string $contentType,
        string $response
    ): GeneralResponse {
        return new GeneralResponse(
            $this,
            $httpCode,
            $url,
            $contentType,
            $response,
            $this->expectedHttpCode
        );
    }
}
