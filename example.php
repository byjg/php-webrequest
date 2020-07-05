<?php

require "vendor/autoload.php";

$httpClient = new \ByJG\Util\HttpClient();


$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterVersao');
$request = \ByJG\Util\Psr7\Request::getInstance($uri);

echo $httpClient->sendRequest($request)->getBody() . "\n";


$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterLogradouro&cep=30130000');
$request = \ByJG\Util\Psr7\Request::getInstance($uri)->withMethod('POST');
$httpClient = new \ByJG\Util\HttpClient('http://www.byjg.com.br/ws/cep');

echo $httpClient->sendRequest($request)->getBody() . "\n";
