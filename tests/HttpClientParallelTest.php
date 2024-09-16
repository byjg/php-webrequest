<?php

namespace Test;

use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\CurlException;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\HttpClient;
use ByJG\WebRequest\Psr7\Request;
use PHPUnit\Framework\TestCase;

class HttpClientParallelTest extends TestCase
{

    protected string $SERVER_TEST;

    public function setUp(): void
    {
        $host = empty(getenv('HTTP_TEST_HOST')) ?  "localhost" : getenv('HTTP_TEST_HOST');
        $port = empty(getenv('HTTP_TEST_PORT')) ?  "8080" : getenv('HTTP_TEST_PORT');

        $this->SERVER_TEST = "http://$host:$port/multirequest.php";
    }

    /**
     * @throws CurlException
     * @throws MessageException
     * @throws RequestException
     */
    public function testMultiRequest(): void
    {
        $httpClient = HttpClient::getInstance();

        $count = 0;
        $results = [];
        $fail = [];

        $onSucess = function ($response, $id) use (&$count, &$results): void {
            $results[] = $response->getStatusCode() . "-" . $response->getBody();
            $count++;
        };

        $onError = function ($error, $id) use (&$fail): void {
            $fail[] = $error;
        };

        $timeStart = time();
        $multi = new \ByJG\WebRequest\HttpClientParallel(
            $httpClient,
            $onSucess,
            $onError
        );

        $request1 = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("param=1"));
        $request2 = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("param=2"));
        $request3 = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("param=3"));

        $multi
            ->addRequest($request1)
            ->addRequest($request2)
            ->addRequest($request3);

        $multi->execute();

        $diffSeconds = time() - $timeStart;
        $this->assertGreaterThan(1, $diffSeconds);
        $this->assertLessThanOrEqual(3, $diffSeconds);

        sort($results);

        $this->assertEquals(3, $count);
        $this->assertEquals(["200-1", "200-2", "200-3"], $results);
    }
}
