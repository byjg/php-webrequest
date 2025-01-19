<?php

namespace ByJG\WebRequest;

enum ContentDisposition: string
{
    case formData = "form-data";
    case inline = "inline";
    case attachement = "attachment";
}
