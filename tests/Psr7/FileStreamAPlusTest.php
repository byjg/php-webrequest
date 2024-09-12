<?php

namespace Test\Psr7;

use ByJG\Util\Psr7\FileStream;
use Psr\Http\Message\StreamInterface;

class FileStreamAPlusTest extends StreamBaseTest
{
    const FILENAME = "/tmp/filestream-test.txt";

    public function getResource(?string $data): StreamInterface
    {
        if (file_exists(self::FILENAME)) {
            unlink(self::FILENAME);
        }
        file_put_contents(self::FILENAME, $data);
        return new FileStream(self::FILENAME, "a+");
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
     * @return false
     */
    public function canOverwrite()
    {
        return false;
    }
}
