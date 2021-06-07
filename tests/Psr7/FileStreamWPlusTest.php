<?php

use ByJG\Util\Psr7\StreamBase;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/StreamBaseTest.php";

class FileStreamTest extends StreamBaseTest
{
    const FILENAME = "/tmp/filestream-test.txt";
    /**
     * @param $data
     * @return StreamBase
     */
    public function getResource($data)
    {
        if (file_exists(self::FILENAME)) {
            unlink(self::FILENAME);
        }
        file_put_contents(self::FILENAME, $data);
        return new \ByJG\Util\Psr7\FileStream(self::FILENAME, "rw+");
    }

    public function tearDownResource()
    {
        $this->stream->close();
        $this->stream = null;
        unlink(self::FILENAME);
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
