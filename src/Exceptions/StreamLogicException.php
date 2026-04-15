<?php

declare(strict_types=1);

namespace MaxMessenger\Uploader\Exceptions;

use MaxMessenger\Bot\Exceptions\MaxApiLogicException;

/**
 * Stream logic exception.
 *
 * Thrown when a logic error occurs while working with streams during the upload process.
 */
final class StreamLogicException extends MaxApiLogicException
{
}
