<?php

namespace ByJG\WebRequest\Factory;

use ByJG\WebRequest\Psr7\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFileFactory implements UploadedFileFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createUploadedFile(StreamInterface $stream, ?int $size = null, int $error = \UPLOAD_ERR_OK, ?string $clientFilename = null, ?string $clientMediaType = null): UploadedFileInterface
    {
        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public static function instance(): UploadedFileFactoryInterface
    {
        return new UploadedFileFactory();
    }
}