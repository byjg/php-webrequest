<?php

use ByJG\Util\HttpClientParallel;

require "vendor/autoload.php";

$httpClientParallel = new HttpClientParallel(
    new \ByJG\Util\HttpClient(),
    function ($body, $id) {
        echo "[$id] => $body\n";
    },
    function ($error, $id) {
        throw new \Exception("$error on id '$id'");
    }
);


$uri1 = \ByJG\Util\Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterLogradouro&cep=21130010');
$request1 = \ByJG\Util\Psr7\Request::getInstance($uri1)->withMethod('POST');

$uri2 = \ByJG\Util\Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterLogradouro&cep=30130000');
$request2 = \ByJG\Util\Psr7\Request::getInstance($uri2)->withMethod('POST');

$httpClientParallel
    ->addRequest($request1)
    ->addRequest($request2)
;

$httpClientParallel->execute();
