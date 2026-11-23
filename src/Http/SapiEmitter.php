<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Sends a PSR-7 response with header() and echo, streaming the body in chunks.
 * Used by StorageNavigator::handleGlobals(); frameworks use their own emitter.
 */
final class SapiEmitter
{
    public function __construct(private readonly int $chunkSize = 65536)
    {
    }

    public function emit(ResponseInterface $response): void
    {
        if (headers_sent($file, $line)) {
            throw new \RuntimeException(sprintf('Cannot send the response: output already started at %s:%d.', $file, $line));
        }
        // Earlier buffered output would corrupt downloads.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            $replace = strcasecmp($name, 'Set-Cookie') !== 0;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $replace);
                $replace = false;
            }
        }

        $body = $response->getBody();
        if ($body instanceof CallbackStream) {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Unable to open php://output.');
            }
            $body->writeTo($output);
            fclose($output);

            return;
        }

        if ($body->isSeekable()) {
            $body->rewind();
        }
        while (!$body->eof()) {
            $chunk = $body->read($this->chunkSize);
            if ($chunk === '') {
                break;
            }
            echo $chunk;
            flush();
            if (connection_aborted() === 1) {
                break;
            }
        }
        $body->close();
    }
}
