<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\Exceptions;

/**
 * Unknown response error.
 *
 * Exception thrown when an unknown error occurs in the API.
 */
final class UnknownResponseErrorException extends UploaderException
{
    public function __construct(
        public readonly string $data
    ) {
        parent::__construct('Unknown data error.');
    }

    /**
     * @return string The error data that caused the exception.
     */
    public function getData(): string
    {
        return $this->data;
    }
}
