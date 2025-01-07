<?php

use ByJG\WebRequest\HttpClientParallel;

require "vendor/autoload.php";

$httpClientParallel = new HttpClientParallel(
    new \ByJG\WebRequest\HttpClient(),
    function ($response, $id) {
        echo "[$id] => " . $response->getStatusCode() . " - " . $response->getBody() . "\n";
    },
    function ($error, $id) {
        echo "[$id] => $error\n";
    }
);


$uri1 = \ByJG\Util\Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterLogradouro&cep=21130010');
$request1 = \ByJG\WebRequest\Psr7\Request::getInstance($uri1)->withMethod(\ByJG\WebRequest\HttpMethod::POST);

$uri2 = \ByJG\Util\Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterLogradouro&cep=30130000');
$request2 = \ByJG\WebRequest\Psr7\Request::getInstance($uri2)->withMethod(\ByJG\WebRequest\HttpMethod::GET);

$httpClientParallel
    ->addRequest($request1)
    ->addRequest($request2)
;

$httpClientParallel->execute();
