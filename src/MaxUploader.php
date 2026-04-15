<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader;

use Closure;
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Models\Enums\UploadType;
use MaxMessenger\Bot\Models\Responses\PhotoTokens;
use MaxMessenger\Bot\Models\Responses\UploadedFile;
use MaxMessenger\Bot\Models\Responses\UploadEndpoint;
use MaxMessenger\Uploader\Contracts\ContentInterface;
use MaxMessenger\Uploader\Exceptions\StreamException;
use MaxMessenger\Uploader\Exceptions\UnknownResponseErrorException;
use MaxMessenger\Uploader\Exceptions\UnknownResponseFormatException;
use MaxMessenger\Uploader\HttpClient\MaxUploadHttpClient;
use MaxMessenger\Uploader\MaxUploader\Content\File;
use MaxMessenger\Uploader\MaxUploader\DTO\Progress;
use MaxMessenger\Uploader\MaxUploader\DTO\UploadEventType;
use Mj4444\SimpleHttpClient\Exceptions\GeneralException;

use function is_bool;
use function is_string;

final class MaxUploader
{
    /**
     * @var Closure(UploadEventType $eventType): void|null
     */
    public ?Closure $eventCallback = null;
    /**
     * @var positive-int
     */
    public int $fragmentLength = PHP_INT_MAX;
    /**
     * @var positive-int
     */
    public int $lowSpeedLimit = 102400;
    /**
     * @var positive-int
     */
    public int $lowSpeedTime = 10;
    /**
     * @var Closure(Progress $progress): void|null
     */
    public ?Closure $progressCallback = null;
    /**
     * @var non-negative-int
     */
    public int $retryAttempts = 100;
    /**
     * @var non-negative-int
     */
    public int $timeout = 0;
    private ?UploadEndpoint $lastMeta = null;

    public function __construct(
        private readonly MaxApiClient $apiClient
    ) {
    }

    public function getLastMeta(): ?UploadEndpoint
    {
        return $this->lastMeta;
    }

    /**
     * @param Closure(UploadEventType $eventType): void|null $eventCallback
     * @return $this
     */
    public function setEventCallback(?Closure $eventCallback): self
    {
        $this->eventCallback = $eventCallback;

        return $this;
    }

    /**
     * @param positive-int $fragmentLength
     * @return $this
     */
    public function setFragmentLength(int $fragmentLength): self
    {
        $this->fragmentLength = $fragmentLength;

        return $this;
    }

    /**
     * @param positive-int $lowSpeedLimit
     * @return $this
     */
    public function setLowSpeedLimit(int $lowSpeedLimit): self
    {
        $this->lowSpeedLimit = $lowSpeedLimit;

        return $this;
    }

    /**
     * @param positive-int $lowSpeedTime
     * @return $this
     */
    public function setLowSpeedTime(int $lowSpeedTime): self
    {
        $this->lowSpeedTime = $lowSpeedTime;

        return $this;
    }

    /**
     * @param Closure(Progress $progress): void|null $progressCallback
     * @return $this
     */
    public function setProgressCallback(?Closure $progressCallback): self
    {
        $this->progressCallback = $progressCallback;

        return $this;
    }

    /**
     * @param non-negative-int $retryAttempts
     * @return $this
     */
    public function setRetryAttempts(int $retryAttempts): self
    {
        $this->retryAttempts = $retryAttempts;

        return $this;
    }

    /**
     * @param non-negative-int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param ContentInterface|non-empty-string $content
     * @return non-empty-string
     */
    public function uploadAudio(ContentInterface|string $content, ?UploadEndpoint $meta = null): string
    {
        if (is_string($content)) {
            $content = new File($content);
        }

        return $this->uploadWithAttempts(
            fn(?UploadEndpoint $meta) => $this->uploadEx($content, UploadType::Audio, $meta, false),
            $meta
        );
    }

    /**
     * @param ContentInterface|non-empty-string $content
     * @return non-empty-string
     */
    public function uploadFile(ContentInterface|string $content, ?UploadEndpoint $meta = null): string
    {
        return $this->uploadFileEx($content, $meta)->getToken();
    }

    /**
     * @param ContentInterface|non-empty-string $content
     */
    public function uploadFileEx(ContentInterface|string $content, ?UploadEndpoint $meta = null): UploadedFile
    {
        if (is_string($content)) {
            $content = new File($content);
        }

        $data = $this->uploadWithAttempts(
            fn(?UploadEndpoint $meta) => $this->uploadEx($content, UploadType::File, $meta, true),
            $meta
        );

        $model = UploadedFile::newFromData($data);
        if (!$model->isValid()) {
            throw new UnknownResponseFormatException($data);
        }

        return $model;
    }

    /**
     * @param ContentInterface|non-empty-string $content
     * @return non-empty-string
     */
    public function uploadImage(ContentInterface|string $content, ?UploadEndpoint $meta = null): string
    {
        $photos = $this->uploadImageEx($content, $meta)->getPhotos();

        $item = reset($photos);

        /** @psalm-suppress DocblockTypeContradiction */
        if ($item === false || empty($token = $item->getToken())) {
            throw new UnknownResponseFormatException($photos);
        }

        return $token;
    }

    /**
     * @param ContentInterface|non-empty-string $content
     */
    public function uploadImageEx(ContentInterface|string $content, ?UploadEndpoint $meta = null): PhotoTokens
    {
        if (is_string($content)) {
            $content = new File($content);
        }

        $data = $this->uploadWithAttempts(
            fn(?UploadEndpoint $meta) => $this->uploadEx($content, UploadType::Image, $meta, true),
            $meta
        );

        $model = PhotoTokens::newFromData($data);
        if (!$model->isValid()) {
            throw new UnknownResponseFormatException($data);
        }

        return $model;
    }

    /**
     * @param ContentInterface|non-empty-string $content
     * @return non-empty-string
     */
    public function uploadVideo(ContentInterface|string $content, ?UploadEndpoint $meta = null): string
    {
        if (is_string($content)) {
            $content = new File($content);
        }

        return $this->uploadWithAttempts(
            fn(?UploadEndpoint $meta) => $this->uploadEx($content, UploadType::Video, $meta, false),
            $meta
        );
    }

    /**
     * @param non-negative-int $frOffset
     * @param positive-int $frLength
     * @param positive-int $size
     * @param Closure(Progress $progress): void $callback
     * @return Closure(non-negative-int $bytesSent): void
     */
    private function makeProgressCallback(int &$frOffset, int &$frLength, int $size, Closure $callback): Closure
    {
        /**
         * @param non-negative-int $bytesSent
         */
        return static function (int $bytesSent) use (&$frOffset, &$frLength, $size, $callback): void {
            $callback(new Progress($frOffset, $frLength, $bytesSent, $size));
        };
    }

    /**
     * @template T of bool
     * @param ContentInterface $content
     * @param T $json
     * @return (T is true ? array : non-empty-string)
     */
    private function uploadEx(
        ContentInterface $content,
        UploadType $type,
        ?UploadEndpoint $meta,
        bool $json
    ): array|string {
        $this->lastMeta = null;
        $bodySize = $content->getSize();
        if ($bodySize < 1) {
            throw new StreamException('Cannot upload empty file.');
        }
        // Размер фрагмента не может быть меньше 1/1000 длины файла или потока,
        // так как количество фрагментов не может быть больше 1000
        $baseFragmentLength = max($this->fragmentLength, (int)($bodySize / 900), 64000);
        $fragmentOffset = 0;
        $fragmentLength = $baseFragmentLength;

        $uploadClient = new MaxUploadHttpClient($this->apiClient->getHttpClient()->getHttpClient());
        $progressCallback = $this->progressCallback !== null
            ? $this->makeProgressCallback($fragmentOffset, $fragmentLength, $bodySize, $this->progressCallback)
            : null;

        if ($meta !== null) {
            if ($this->eventCallback !== null) {
                ($this->eventCallback)(UploadEventType::FileInfoRequest);
            }

            // Получаем с сервера количество загруженных данных
            $uploadInfo = $uploadClient->getResumableUploadInfo($meta->getUrl());
            if (!is_bool($uploadInfo) && $uploadInfo->totalBytes === $bodySize) {
                // Продолжаем загрузку
                if ($this->eventCallback !== null) {
                    ($this->eventCallback)(UploadEventType::Resume);
                }
                $fragmentOffset = $uploadInfo->lastLoadedByte + 1;
            } else {
                // Начинаем загрузку сначала
                if ($this->eventCallback !== null) {
                    ($this->eventCallback)(UploadEventType::Reset);
                }
                if ($uploadInfo === false) {
                    // Нужно получить новый url для загрузки
                    $meta = null;
                }
            }
        }
        if ($meta === null) {
            if ($this->eventCallback !== null) {
                ($this->eventCallback)(UploadEventType::UrlRequest);
            }

            // Получаем новый url для загрузки
            $meta = $this->apiClient->getUploadUrl($type);
        }
        $this->lastMeta = $meta;

        if (!$json) {
            $token = $meta->getToken() ?? throw new UnknownResponseFormatException($meta->getRawData());
        }

        while ($fragmentOffset < $bodySize) {
            if ($this->eventCallback !== null) {
                ($this->eventCallback)(UploadEventType::SendFragment);
            }

            /** @var positive-int $fragmentLength $bodySize - $fragmentOffset > 0, $baseFragmentLength > 0 */
            $fragmentLength = min($bodySize - $fragmentOffset, $baseFragmentLength);

            $data = $uploadClient->postResumableUpload(
                $meta->getUrl(),
                $content,
                $fragmentOffset,
                $fragmentLength,
                $this->timeout,
                $this->lowSpeedLimit,
                $this->lowSpeedTime,
                $progressCallback,
                $json
            );

            if (!$json) {
                $pos = $fragmentOffset + $fragmentLength - 1;
                if ($data !== "0-$pos/$bodySize") {
                    throw new UnknownResponseErrorException($data);
                }
            }

            $fragmentOffset += $fragmentLength;
        }

        /**
         * @psalm-suppress PossiblyUndefinedVariable
         * @var (T is true ? array : non-empty-string)
         */
        return $json ? $data : $token;
    }

    /**
     * @template T
     * @param Closure(?UploadEndpoint $meta): T $callback
     * @return T
     */
    private function uploadWithAttempts(Closure $callback, ?UploadEndpoint $meta): mixed
    {
        $attempts = $this->retryAttempts;
        do {
            try {
                return $callback($meta);
            } catch (GeneralException $e) {
                if ($e->getCode() === GeneralException::OPERATION_TIMEOUTED) {
                    if ($this->eventCallback !== null) {
                        ($this->eventCallback)(UploadEventType::Timeout);
                    }

                    $meta = $this->lastMeta;

                    continue;
                }
                throw $e;
            }
        } while ($attempts-- > 0);

        /** @psalm-suppress PossiblyUndefinedVariable */
        throw $e;
    }
}
