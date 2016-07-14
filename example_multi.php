<?php

use ByJG\Util\WebRequestMulti;

require "vendor/autoload.php";

$webRequestMulti = new WebRequestMulti(
    function ($body, $id) {
        echo "[$id] => $body\n";
    },
    function ($error, $id) {
        throw new \Exception("$error on id '$id'");
    }
);


$webRequestMulti
    ->addRequest(
        new \ByJG\Util\WebRequest('http://does.not.exists'),
        WebRequestMulti::GET,
        [
            'httpmethod' => 'obterLogradouro',
            'cep' => '21130010'
        ]
    )
    ->addRequest(
        new \ByJG\Util\WebRequest('http://www.byjg.com.br/ws/cep'),
        WebRequestMulti::GET,
        [
            'httpmethod' => 'obterLogradouro',
            'cep' => '30130000'
        ]
    );

$webRequestMulti->execute();
