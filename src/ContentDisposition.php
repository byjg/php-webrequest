<?php

namespace ByJG\WebRequest;

enum ContentDisposition: string
{
    case FormData = "form-data";
    case Inline = "inline";
    case Attachment = "attachment";
}
