<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\MaxUploader\Content;

use MaxMessenger\Uploader\Exceptions\StreamException;

final readonly class File extends BaseStream
{
    /**
     * @param non-empty-string $fileName Имя существующего файла.
     * @param non-empty-string|null $postName Имя файла, используемое при загрузке.
     */
    public function __construct(
        public string $fileName,
        ?string $postName = null,
    ) {
        $resource = @fopen($this->fileName, 'rb');

        if (!$resource) {
            throw new StreamException('Unable to open file: ' . $this->fileName);
        }

        parent::__construct($resource, $postName ?? $fileName);
    }
}
