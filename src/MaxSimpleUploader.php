<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader;

use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Models\Enums\UploadType;
use MaxMessenger\Bot\Models\Responses\PhotoTokens;
use MaxMessenger\Bot\Models\Responses\UploadedFile;
use MaxMessenger\Uploader\Exceptions\UnknownResponseErrorException;
use MaxMessenger\Uploader\Exceptions\UnknownResponseFormatException;
use MaxMessenger\Uploader\HttpClient\MaxUploadHttpClient;
use MaxMessenger\Uploader\MaxSimpleUploader\File;
use MaxMessenger\Uploader\MaxSimpleUploader\StringFile;

use function is_string;

final class MaxSimpleUploader
{
    /**
     * @var positive-int
     */
    public int $lowSpeedLimit = 102400;
    /**
     * @var positive-int
     */
    public int $lowSpeedTime = 10;

    public function __construct(
        private readonly MaxApiClient $apiClient
    ) {
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
     * @param File|StringFile|non-empty-string $file
     * @return non-empty-string
     */
    public function uploadAudio(File|StringFile|string $file): string
    {
        $meta = $this->apiClient->getUploadUrl(UploadType::Audio);

        $token = $meta->getToken() ?? throw new UnknownResponseFormatException($meta->getRawData());

        $data = $this->postSimpleUpload($meta->getUrl(), $file, false);

        if ($data !== '<retval>1</retval>') {
            throw new UnknownResponseErrorException($data);
        }

        return $token;
    }

    /**
     * @param File|StringFile|non-empty-string $file
     * @return non-empty-string
     */
    public function uploadFile(File|StringFile|string $file): string
    {
        return $this->uploadFileEx($file)->getToken();
    }

    /**
     * @param File|StringFile|non-empty-string $file
     * @return UploadedFile
     */
    public function uploadFileEx(File|StringFile|string $file): UploadedFile
    {
        $meta = $this->apiClient->getUploadUrl(UploadType::File);

        $data = $this->postSimpleUpload($meta->getUrl(), $file, true);

        $model = UploadedFile::newFromData($data);

        if (!$model->isValid()) {
            throw new UnknownResponseFormatException($data);
        }

        return $model;
    }

    /**
     * @param File|StringFile|non-empty-string $file
     * @return non-empty-string
     */
    public function uploadImage(File|StringFile|string $file): string
    {
        $photos = $this->uploadImageEx($file)->getPhotos();

        $item = reset($photos);

        /** @psalm-suppress DocblockTypeContradiction */
        if ($item === false || empty($token = $item->getToken())) {
            throw new UnknownResponseFormatException($photos);
        }

        return $token;
    }

    /**
     * @param File|StringFile|non-empty-string $file
     * @return PhotoTokens
     */
    public function uploadImageEx(File|StringFile|string $file): PhotoTokens
    {
        $meta = $this->apiClient->getUploadUrl(UploadType::Image);

        $data = $this->postSimpleUpload($meta->getUrl(), $file, true);

        $model = PhotoTokens::newFromData($data);
        if (!$model->isValid()) {
            throw new UnknownResponseFormatException($data);
        }

        return $model;
    }

    /**
     * @param File|StringFile|non-empty-string $file
     * @return non-empty-string
     */
    public function uploadVideo(File|StringFile|string $file): string
    {
        $meta = $this->apiClient->getUploadUrl(UploadType::Video);

        $token = $meta->getToken() ?? throw new UnknownResponseFormatException($meta->getRawData());

        $data = $this->postSimpleUpload($meta->getUrl(), $file, false);

        if ($data !== '<retval>1</retval>') {
            throw new UnknownResponseErrorException($data);
        }

        return $token;
    }

    /**
     * @template T of bool
     * @param non-empty-string $url
     * @param File|StringFile|non-empty-string $file
     * @param T $json
     * @return (T is true ? array : string)
     */
    private function postSimpleUpload(string $url, File|StringFile|string $file, bool $json): array|string
    {
        if (is_string($file)) {
            $file = new File($file);
        }

        return (new MaxUploadHttpClient($this->apiClient->getHttpClient()->getHttpClient()))
            ->postSimpleUpload($url, $file, $this->lowSpeedLimit, $this->lowSpeedTime, $json);
    }
}
