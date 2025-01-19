<?php

use ByJG\Util\Uri;
use ByJG\WebRequest\HttpClientParallel;
use ByJG\WebRequest\HttpMethod;
use ByJG\WebRequest\Psr7\Request;

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


$uri1 = Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterLogradouro&cep=21130010');
$request1 = Request::getInstance($uri1)->withMethod(HttpMethod::POST);

$uri2 = Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterLogradouro&cep=30130000');
$request2 = Request::getInstance($uri2)->withMethod(HttpMethod::GET);

$httpClientParallel
    ->addRequest($request1)
    ->addRequest($request2)
;

$httpClientParallel->execute();
