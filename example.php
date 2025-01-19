<?php

use ByJG\Util\Uri;
use ByJG\WebRequest\HttpClient;
use ByJG\WebRequest\HttpMethod;
use ByJG\WebRequest\Psr7\Request;

require "vendor/autoload.php";

$httpClient = new HttpClient();


$uri = Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterVersao');
$request = Request::getInstance($uri);

echo $httpClient->sendRequest($request)->getBody() . "\n";


$uri = Uri::getInstanceFromString('http://www.byjg.com.br/ws/cep?httpmethod=obterLogradouro&cep=30130000');
$request = Request::getInstance($uri)->withMethod(HttpMethod::POST);
$httpClient = new HttpClient('http://www.byjg.com.br/ws/cep');

echo $httpClient->sendRequest($request)->getBody() . "\n";
