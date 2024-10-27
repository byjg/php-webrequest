<?php

namespace Test\Psr7;

use ByJG\WebRequest\Psr7\FileStream;
use Psr\Http\Message\StreamInterface;

class FileStreamWPlusTest extends StreamBaseTest
{
    const FILENAME = "/tmp/filestream-test.txt";

    public function getResource(?string $data): StreamInterface
    {
        if (file_exists(self::FILENAME)) {
            unlink(self::FILENAME);
        }
        file_put_contents(self::FILENAME, $data);
        return new FileStream(self::FILENAME, "rw+");
    }

    /**
     * @return void
     */
    public function tearDownResource()
    {
        $this->stream->close();
        $this->stream = null;
        unlink(self::FILENAME);
    }
    /**
     * @return true
     */
    public function isWriteable()
    {
        return true;
    }

    /**
     * @return true
     */
    public function canOverwrite()
    {
        return true;
    }
}
