<?php

namespace ByJG\Util\Psr7;

use \RuntimeException;

class FileStream extends StreamBase
{
    public function __construct($file, $mode = "rw+")
    {
        if (!file_exists($file)) {
            throw new RuntimeException("File $file doesn't exists");
        }
        $this->resource = fopen($file, $mode);
        parent::__construct();
    }
}