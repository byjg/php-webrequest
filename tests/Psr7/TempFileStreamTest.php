<?php

use ByJG\Util\Psr7\StreamBase;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/StreamBaseTest.php";

class TempFileStreamTest extends StreamBaseTest
{
    /**
     * @param $data
     * @return StreamBase
     */
    public function getResource($data)
    {
        return new \ByJG\Util\Psr7\TempFileStream($data);
    }

    public function tearDownResource()
    {
        $this->stream->close();
        $this->stream = null;
    }

    public function isWriteable()
    {
        return true;
    }

    public function canOverwrite()
    {
        return true;
    }
}
