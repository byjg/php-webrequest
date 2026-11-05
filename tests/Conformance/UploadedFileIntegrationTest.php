<?php

namespace Tests\Conformance;

use ByJG\WebRequest\Factory\StreamFactory;
use ByJG\WebRequest\Factory\UploadedFileFactory;
use Http\Psr7Test\UploadedFileIntegrationTest as Psr7UploadedFileIntegrationTest;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFileIntegrationTest extends Psr7UploadedFileIntegrationTest
{
    #[\Override]
    public function createSubject(): UploadedFileInterface
    {
        $stream = (new StreamFactory())->createStream('writing to tempfile');
        return (new UploadedFileFactory())->createUploadedFile($stream, 19, UPLOAD_ERR_OK);
    }
}
