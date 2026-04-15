<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\HttpClient;

use JsonException;
use MaxMessenger\Bot\Exceptions\HttpClient\HttpResponse\JsonDecodeException;
use MaxMessenger\Bot\Exceptions\HttpClient\HttpResponse\UnexpectedContentTypeException;
use MaxMessenger\Bot\HttpClient\Exceptions\HttpResponse\Http\MaxHttpException;
use MaxMessenger\Bot\HttpClient\Exceptions\HttpResponse\Http\UnknownException;
use MaxMessenger\Bot\Models\Responses\Error;
use MaxMessenger\Uploader\Exceptions\UnknownResponseFormatException;
use Mj4444\SimpleHttpClient\Contracts\HttpResponseInterface;
use Mj4444\SimpleHttpClient\Exceptions\HttpResponse\Http\HttpException;

use function is_array;

/**
 * Универсальный HTTP-ответ загрузчиков.
 *
 * @implements HttpResponseInterface<BaseRequest>
 * @internal
 */
final readonly class GeneralResponse implements HttpResponseInterface
{
    public function __construct(
        public BaseRequest $request,
        public int $httpCode,
        public string $url,
        public ?string $contentType,
        public string $body,
        public int $expectedHttpCode = 200
    ) {
    }

    public function checkContentType(string|array|null $expectedContentType = null): void
    {
        if (!str_starts_with($this->contentType ?? '', 'application/json')) {
            /** @psalm-var HttpResponseInterface $this Psalm bug */
            throw new UnexpectedContentTypeException($this);
        }
    }

    public function checkHttpCode(int|array $allowedCode = 200): void
    {
        if ($this->getHttpCode() !== $this->expectedHttpCode) {
            $error = Error::newFromData((array)$this->getData());

            /** @psalm-var HttpResponseInterface $this Psalm bug */
            $error->isValid()
                ? MaxHttpException::throwMax($this, $error)
                : HttpException::throw($this, [200]);
        }
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getData(): mixed
    {
        $this->checkContentType();

        try {
            /** @psalm-suppress MixedAssignment */
            $data = json_decode($this->body, true, 4, JSON_THROW_ON_ERROR);

            if (is_array($data) && isset($data['error_code'], $data['error_data'])) {
                $error = Error::newFromData(['code' => $data['error_code'], 'message' => $data['error_data']]);
                if ($error->isValid()) {
                    /** @psalm-var HttpResponseInterface $this Psalm bug */
                    throw new UnknownException($this, $error);
                }

                throw new UnknownResponseFormatException($data);
            }

            return $data;
        } catch (JsonException $e) {
            /** @psalm-var HttpResponseInterface $this Psalm bug */
            throw new JsonDecodeException($this, $e);
        }
    }

    public function getEffectiveUrl(): string
    {
        return $this->url;
    }

    public function getFirstHeader(string $name): null
    {
        return null;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getRedirectUrl(): null
    {
        return null;
    }

    public function getRequest(): BaseRequest
    {
        return $this->request;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
