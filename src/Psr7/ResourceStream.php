<?php

namespace ByJG\WebRequest\Psr7;

use InvalidArgumentException;

/**
 * A stream over a resource opened elsewhere. The resource is used as it is, at its current
 * position, and closing the stream closes it.
 */
class ResourceStream extends StreamBase
{
    /**
     * @param resource $resource
     */
    public function __construct(mixed $resource)
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new InvalidArgumentException("A stream resource is required");
        }
        $this->resource = $resource;
        parent::__construct();
    }
}
