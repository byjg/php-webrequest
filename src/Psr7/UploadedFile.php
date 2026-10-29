<?php

namespace ByJG\WebRequest\Psr7;

use ByJG\WebRequest\Factory\StreamFactory;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile implements UploadedFileInterface
{
    protected StreamInterface $stream;
    protected bool $moved = false;

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
        if ($this->moved) {
            throw new RuntimeException("The uploaded file was moved");
        }
        return $this->stream;
    }

    #[\Override]
    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new RuntimeException("The uploaded file was already moved");
        }
        if (!is_writable(dirname($targetPath))) {
            throw new RuntimeException("Target path is not writable");
        }

        // A file on disk is moved, not copied: move_uploaded_file() for one PHP received
        $path = $this->filePath();
        if ($path !== null) {
            $moved = is_uploaded_file($path) ? move_uploaded_file($path, $targetPath) : rename($path, $targetPath);
            if (!$moved) {
                throw new RuntimeException("Could not move the uploaded file to $targetPath");
            }
        } else {
            $this->stream->rewind();
            file_put_contents($targetPath, $this->stream->getContents());
        }
        $this->moved = true;
    }

    /**
     * The path of the file the stream reads, when it is a plain file
     */
    private function filePath(): ?string
    {
        $metadata = $this->stream->getMetadata();
        if (!is_array($metadata) || ($metadata['wrapper_type'] ?? '') !== 'plainfile' || !is_file((string)$metadata['uri'])) {
            return null;
        }
        return (string)$metadata['uri'];
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
        // The temporary file is read through the stream when used, never loaded in advance
        $stream = '';
        if ($file['error'] == UPLOAD_ERR_OK && is_file($file['tmp_name'])) {
            $stream = new FileStream($file['tmp_name'], 'r');
        }

        return new UploadedFile(
            $stream,
            $file['size'],
            $file['error'],
            $file['name'],
            $file['type']
        );
    }
}