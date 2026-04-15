<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\HttpClient;

use Closure;
use MaxMessenger\Uploader\Contracts\ContentInterface;
use MaxMessenger\Uploader\Exceptions\UnknownResponseErrorException;
use MaxMessenger\Uploader\MaxUploader\DTO\ResumableUploadInfo;
use Mj4444\SimpleHttpClient\Contracts\HttpClientInterface;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\FileInterface;
use Mj4444\SimpleHttpClient\Contracts\HttpRequest\StringFileInterface;
use Mj4444\SimpleHttpClient\Contracts\HttpRequestInterface;
use Mj4444\SimpleHttpClient\Contracts\HttpResponseInterface;

use function is_array;

final readonly class MaxUploadHttpClient
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    /**
     * Получает с сервера информацию о загруженных данных.
     *
     * @param non-empty-string $url
     * @return ResumableUploadInfo|bool Возвращает `ResumableUploadInfo`, если загрузка может быть продолжена,
     *     `true`, если загрузка должна быть начата с начала и `false`, если загрузка не может быть возобновлена.
     */
    public function getResumableUploadInfo(string $url): ResumableUploadInfo|bool
    {
        $response = $this->httpClient->request(new ResumableInfoRequest($url));
        $httpCode = $response->getHttpCode();

        if ($httpCode === 200) {
            $body = $response->getBody();
            if (preg_match('/^0-(\d+)\/(\d+)$/', $body, $matches)) {
                /** @psalm-suppress ArgumentTypeCoercion */
                return new ResumableUploadInfo((int)$matches[1], (int)$matches[2]);
            }
        }

        return $httpCode === 204;
    }

    /**
     * @template T of bool
     * @param non-empty-string $url
     * @param ContentInterface $content
     * @param non-negative-int $offset
     * @param positive-int $length
     * @param non-negative-int $timeout
     * @param positive-int $lowSpeedLimit
     * @param positive-int $lowSpeedTime
     * @param Closure(non-negative-int $bytesSent, non-negative-int $totalBytes): void|null $progressCallback
     * @param T $json
     * @return (T is true ? array : string)
     */
    public function postResumableUpload(
        string $url,
        ContentInterface $content,
        int $offset,
        int $length,
        int $timeout,
        int $lowSpeedLimit,
        int $lowSpeedTime,
        ?Closure $progressCallback,
        bool $json
    ): array|string {
        $request = new ResumableUploadRequest(
            $url,
            $content,
            $offset,
            $length,
            $timeout,
            $lowSpeedLimit,
            $lowSpeedTime,
            $progressCallback,
            $json
        );

        /** @psalm-suppress InvalidArgument Psalm bug */
        return $this->doRequest($request, $json);
    }

    /**
     * @template T of bool
     * @param non-empty-string $url
     * @param positive-int $lowSpeedLimit
     * @param positive-int $lowSpeedTime
     * @param T $json
     * @return (T is true ? array : string)
     */
    public function postSimpleUpload(
        string $url,
        FileInterface|StringFileInterface $file,
        int $lowSpeedLimit,
        int $lowSpeedTime,
        bool $json
    ): array|string {
        $request = new SimpleUploadRequest($url, $file, $lowSpeedLimit, $lowSpeedTime, $json);

        /** @psalm-suppress InvalidArgument Psalm bug */
        return $this->doRequest($request, $json);
    }

    /**
     * @template T of bool
     * @param T $json
     * @return (T is true ? array : string)
     */
    private function doRequest(HttpRequestInterface $request, bool $json): array|string
    {
        $response = $this->httpClient->request($request);

        $response->checkHttpCode();

        if (!$json) {
            return $response->getBody();
        }

        $responseData = $response->getData();

        if (!is_array($responseData) || empty($responseData)) {
            /** @psalm-var HttpResponseInterface $response Psalm bug */
            throw new UnknownResponseErrorException($response->getBody());
        }

        return $responseData;
    }
}
