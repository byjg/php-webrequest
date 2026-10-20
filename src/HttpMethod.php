<?php

namespace ByJG\WebRequest;

enum HttpMethod: string
{
    case Get = "GET";
    case Head = "HEAD";
    case Post = "POST";
    case Put = "PUT";
    case Delete = "DELETE";
    case Connect = "CONNECT";
    case Options = "OPTIONS";
    case Trace = "TRACE";
    case Patch = "PATCH";
}
