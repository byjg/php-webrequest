<?php

/**
 * @param string $param1
 * @param int $param2
 * @return string
 */
function test($param1, $param2)
{
    return "$param1 - $param2";
}

$server = new SoapServer(
    null,
    [
        'uri' => "http://localhost:8080/"
    ]
);
$server->addFunction("test");
$server->handle();
