<?php

namespace Tests\Conformance;

use ByJG\WebRequest\Factory\StreamFactory;
use Http\Psr7Test\StreamIntegrationTest as Psr7StreamIntegrationTest;
use Psr\Http\Message\StreamInterface;

class StreamIntegrationTest extends Psr7StreamIntegrationTest
{
    /**
     * @param string|resource|StreamInterface $data
     */
    #[\Override]
    public function createStream($data): StreamInterface
    {
        if ($data instanceof StreamInterface) {
            return $data;
        }
        $factory = new StreamFactory();
        return is_resource($data) ? $factory->createStreamFromResource($data) : $factory->createStream($data);
    }
}
