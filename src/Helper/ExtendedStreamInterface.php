<?php


namespace ByJG\WebRequest\Helper;


use Psr\Http\Message\StreamInterface;

interface ExtendedStreamInterface
{
    function appendStream(StreamInterface $stream): void;

    function addFilter(string $filter, string $mode = "r"): void;
}