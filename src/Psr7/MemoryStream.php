<?php

namespace ByJG\Util\Psr7;

class MemoryStream extends StreamBase
{
    public function __construct($data = null)
    {
        $this->resource = fopen("php://memory", "rw");
        parent::__construct();
        if (!empty($data)) {
            $this->write($data);
        }
        $this->rewind();
    }
}
