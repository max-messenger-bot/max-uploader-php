<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\MaxUploader\DTO;

/**
 * Прогресс загрузки потока.
 */
final class Progress
{
    /**
     * @param non-negative-int $fragmentOffset Смещение текущего фрагмента от начала данных.
     * @param non-negative-int $fragmentLength Длина текущего фрагмента.
     * @param non-negative-int $bytesSent Количество переданных байт в пределах фрагмента.
     * @param non-negative-int $size Общее количество байт.
     */
    public function __construct(
        public int $fragmentOffset,
        public int $fragmentLength,
        public int $bytesSent,
        public int $size
    ) {
    }
}
