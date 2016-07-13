<?php

use ByJG\Util\WebRequestMulti;

require "vendor/autoload.php";

$webRequestMulti = new WebRequestMulti();

$webRequestMulti
    ->addRequest(
        new \ByJG\Util\WebRequest('http://www.byjg.com.br/ws/cep'),
        WebRequestMulti::GET,
        [
            'httpmethod' => 'obterLogradouro',
            'cep' => '30130000'
        ]
    )
    ->addRequest(
        new \ByJG\Util\WebRequest('http://www.byjg.com.br/ws/cep'),
        WebRequestMulti::GET,
        [
            'httpmethod' => 'obterLogradouro',
            'cep' => '21130010'
        ]
    );

$result = $webRequestMulti->execute();

print_r($result);
