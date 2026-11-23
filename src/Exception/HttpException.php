<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * An exception caused by the client request. Its message is safe to show to the user,
 * and statusCode() tells which HTTP status to answer with.
 */
interface HttpException extends StorageNavigatorException
{
    public function statusCode(): int;
}
