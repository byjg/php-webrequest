<?php


namespace ByJG\Util\Helper;


interface ExtendedStreamInterface
{
    function appendStream($stream);

    function addFilter($filter);
}