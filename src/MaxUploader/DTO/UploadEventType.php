<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\MaxUploader\DTO;

/**
 * События загрузчика MaxUploader.
 *
 * Возможные значения:
 * - `FileInfoRequest` — Генерируется перед запросом информации о состоянии загружаемого файла.
 * - `SendFragment` — Генерируется перед началом загрузки очередного фрагмента.
 * - `Reset` — Генерируется при перезапуске загрузки с начала.
 * - `Resume` — Генерируется при возобновлении загрузки.
 * - `Timeout` — Генерируется при получении исключения с таймаутом.
 * - `UrlRequest` — Генерируется перед запросом ссылки для загрузки.
 */
enum UploadEventType
{
    case FileInfoRequest;
    case SendFragment;
    case Reset;
    case Resume;
    case Timeout;
    case UrlRequest;
}
