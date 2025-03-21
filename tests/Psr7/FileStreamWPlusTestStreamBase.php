<?php

namespace Test\Psr7;

use ByJG\WebRequest\Psr7\FileStream;
use Psr\Http\Message\StreamInterface;

class FileStreamWPlusTestStreamBase extends TestStreamBase
{
    const FILENAME = "/tmp/filestream-test.txt";

    #[\Override]
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
    #[\Override]
    public function tearDownResource()
    {
        $this->stream->close();
        $this->stream = null;
        unlink(self::FILENAME);
    }
    /**
     * @return true
     */
    #[\Override]
    public function isWriteable()
    {
        return true;
    }

    /**
     * @return true
     */
    #[\Override]
    public function canOverwrite()
    {
        return true;
    }
}
