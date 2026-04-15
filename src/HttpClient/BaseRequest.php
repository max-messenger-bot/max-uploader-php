<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\HttpClient;

use Mj4444\SimpleHttpClient\Contracts\HttpRequestInterface;

/**
 * @implements HttpRequestInterface<GeneralResponse>
 * @internal
 */
abstract readonly class BaseRequest implements HttpRequestInterface
{
    public function getConnectTimeout(): null
    {
        return null;
    }

    public function getMaxRedirects(): null
    {
        return null;
    }

    public function getTimeout(): ?int
    {
        return null;
    }

    public function isFollowLocation(): false
    {
        return false;
    }

    public function isResponseHeadersRequired(): false
    {
        return false;
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
            $response
        );
    }
}
