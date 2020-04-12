<?php

namespace ByJG\Util;

class MultiPartItem
{
    protected $field;

    protected $content;

    protected $filename;

    protected $contentType;

    /**
     * MultiPartItem constructor.
     *
     * @param $field
     * @param $content
     * @param $filename
     * @param $contentType
     */
    public function __construct($field, $content = "", $filename = "", $contentType = "")
    {
        $this->field = $field;
        $this->content = $content;
        $this->filename = $filename;
        $this->contentType = $contentType;
    }

    /**
     * @param $filename
     * @param string $contentType
     * @throws FileNotFoundException
     */
    public function loadFile($filename, $contentType = "")
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException("File '$filename' does not found!");
        }

        $this->content = file_get_contents($filename);
        $this->filename = basename($filename);
        $this->contentType = $contentType;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }
}
