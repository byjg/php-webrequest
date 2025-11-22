<?php

namespace ByJG\WebRequest\Psr7;

use ByJG\WebRequest\Factory\StreamFactory;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    protected StreamInterface $stream;

    public function __construct(
        StreamInterface|string $stream,
        protected ?int $size = null,
        protected int $error = \UPLOAD_ERR_OK,
        protected ?string $clientFilename = null,
        protected ?string $clientMediaType = null
    )
    {
        if (is_string($stream)) {
            $this->stream = StreamFactory::instance()->createStream($stream);
        } else {
            $this->stream = $stream;
        }

        if (empty($this->size)) {
            $this->size = $this->stream->getSize();
        }
    }

    #[\Override]
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    #[\Override]
    public function moveTo(string $targetPath): void
    {
        $this->stream->rewind();

        if (!is_writable(dirname($targetPath))) {
            throw new \RuntimeException("Target path is not writable");
        }

        file_put_contents($targetPath, $this->stream->getContents());
    }

    #[\Override]
    public function getSize(): ?int
    {
        return $this->size;
    }

    #[\Override]
    public function getError(): int
    {
        return $this->error;
    }

    #[\Override]
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    #[\Override]
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    public static function parseFilesGlobal(?array $files = null): array
    {
        if (empty($files)) {
            $files = $_FILES;
        }

        $uploadedFiles = [];
        foreach ($files as $key => $file) {
            $uploadedFiles[$key] = is_array($file['name'])
                ? self::parseNestedFiles($file)
                : self::createUploadedFile($file);
        }

        return $uploadedFiles;
    }

    private static function parseNestedFiles(array $file, array $keys = []): array
    {
        $uploadedFiles = [];
        foreach ($file['name'] as $index => $name) {
            $currentKeys = array_merge($keys, [$index]);
            if (is_array($name)) {
                $uploadedFiles[$index] = self::parseNestedFiles([
                    'name' => $file['name'][$index],
                    'type' => $file['type'][$index],
                    'tmp_name' => $file['tmp_name'][$index],
                    'error' => $file['error'][$index],
                    'size' => $file['size'][$index],
                ], $currentKeys);
            } else {
                $uploadedFiles[$index] = self::createUploadedFile([
                    'name' => $name,
                    'type' => $file['type'][$index],
                    'tmp_name' => $file['tmp_name'][$index],
                    'error' => $file['error'][$index],
                    'size' => $file['size'][$index],
                ]);
            }
        }
        return $uploadedFiles;
    }

    private static function createUploadedFile(array $file): UploadedFile
    {
        $content = '';
        if ($file['error'] == UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($file['tmp_name']);
            $content = $fileContent !== false ? $fileContent : '';
        }

        return new UploadedFile(
            $content,
            $file['size'],
            $file['error'],
            $file['name'],
            $file['type']
        );
    }
}