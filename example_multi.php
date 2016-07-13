<?php

use ByJG\Util\WebRequestMulti;

require "vendor/autoload.php";

$webRequestMulti = new WebRequestMulti();

$onSuccess = function ($body) {
    echo $body . "\n";
};

$webRequestMulti
    ->addRequest(
        new \ByJG\Util\WebRequest('http://www.byjg.com.br/ws/cep'),
        WebRequestMulti::GET,
        [
            'httpmethod' => 'obterLogradouro',
            'cep' => '30130000'
        ],
        $onSuccess
    )
    ->addRequest(
        new \ByJG\Util\WebRequest('http://www.byjg.com.br/ws/cep'),
        WebRequestMulti::GET,
        [
            'httpmethod' => 'obterLogradouro',
            'cep' => '21130010'
        ],
        $onSuccess
    );

$webRequestMulti->execute();
