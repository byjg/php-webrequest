<?php

use ByJG\Util\MultiPartItem;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Uri;
use ByJG\Util\WebRequest;
use PHPUnit\Framework\TestCase;

class WebRequestMultiTest extends TestCase
{

    const SERVER_TEST = 'http://localhost:8080/multirequest.php';

    protected function setUp()
    {

    }

    public function testMultiRequest()
    {
        $httpClient = \ByJG\Util\HttpClient::getInstance();

        $count = 0;
        $results = [];
        $fail = [];

        $onSucess = function ($body, $id) use (&$count, &$results) {
            $results[] = $body;
            $count++;
        };

        $onError = function ($error, $id) use (&$fail) {
            $fail[] = $error;
        };

        $multi = new \ByJG\Util\WebRequestMulti(
            $httpClient,
            $onSucess,
            $onError
        );

        $request1 = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("param=1"));
        $request2 = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("param=2"));
        $request3 = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("param=3"));

        $multi
            ->addRequest($request1)
            ->addRequest($request2)
            ->addRequest($request3);

        $multi->execute();

        sort($results);

        $this->assertEquals(3, $count);
        $this->assertEquals([1, 2, 3], $results);
    }
}
