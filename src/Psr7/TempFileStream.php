<?php

namespace ByJG\Util\Psr7;

class TempFileStream extends StreamBase
{
    public function __construct($data = null)
    {
        $this->resource = fopen("php://temp", "rw+");
        parent::__construct();
        if (!empty($data)) {
            $this->write($data);
        }
        $this->rewind();
    }
}
