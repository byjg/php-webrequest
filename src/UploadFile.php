<?php

namespace ByJG\Util;

class UploadFile
{
    protected $field;

    protected $content;

    protected $filename;

    public function __construct($field, $content = "", $filename = "")
    {
        $this->field = $field;
        $this->content = $content;
        $this->filename = $filename;
    }

    public function loadFile($filename)
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException("File '$filename' does not found!");
        }

        $this->content = file_get_contents($filename);
        $this->filename = basename($filename);
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
}
