<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\MaxUploader\DTO;

final class ResumableUploadInfo
{
    /**
     * @param non-negative-int $lastLoadedByte
     * @param non-negative-int $totalBytes
     */
    public function __construct(
        public int $lastLoadedByte,
        public int $totalBytes,
    ) {
    }
}
