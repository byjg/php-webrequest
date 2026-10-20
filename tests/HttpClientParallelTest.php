<?php

namespace Tests;

use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\CurlException;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\HttpClient;
use ByJG\WebRequest\HttpClientParallel;
use ByJG\WebRequest\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class HttpClientParallelTest extends TestCase
{

    protected string $SERVER_TEST;

    #[\Override]
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
        $this->assertEquals([], $fail);
    }

    public function testFailedConnectionCallsOnErrorOnce(): void
    {
        $successIds = [];
        $errorIds = [];

        $multi = new HttpClientParallel(
            HttpClient::getInstance(),
            function ($response, $id) use (&$successIds): void {
                $successIds[] = $id;
            },
            function ($error, $id) use (&$errorIds): void {
                $errorIds[] = $id;
            }
        );

        $multi
            ->addRequest(Request::getInstance(Uri::getInstanceFromString("http://localhost:1/")))
            ->addRequest(Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("param=1")));

        $multi->execute();

        $this->assertEquals([0], $errorIds);
        $this->assertEquals([1], $successIds);
        $this->assertEquals([], $multi->getErrorList());
    }

    public function testErrorListContainsOnlyThrowables(): void
    {
        $onSuccess = function ($response, $id): void {
            if ($id === 1) {
                throw new \RuntimeException("Handler failed");
            }
        };

        // A response that cannot be parsed (param=1) and a handler that throws (id 1)
        $multi = new class(HttpClient::getInstance(), $onSuccess) extends HttpClientParallel {
            #[\Override]
            public function parseCurl(string $body, $curlHandle, bool $close = true): ResponseInterface
            {
                if (str_contains((string)curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL), "param=1")) {
                    throw new MessageException("Cannot parse");
                }
                return parent::parseCurl($body, $curlHandle, $close);
            }
        };

        $multi
            ->addRequest(Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("param=1")))
            ->addRequest(Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("param=2")));

        try {
            $multi->execute();
            $this->fail("CurlException expected");
        } catch (CurlException $ex) {
            $this->assertStringStartsWith("Raised 2 error(s).", $ex->getMessage());
            $this->assertStringContainsString(MessageException::class . ": Cannot parse", $ex->getMessage());
            $this->assertStringContainsString(\RuntimeException::class . ": Handler failed", $ex->getMessage());
        }

        $errors = $multi->getErrorList();
        $this->assertCount(2, $errors);
        $this->assertContainsOnlyInstancesOf(\Throwable::class, $errors);
        $messages = array_map(fn(\Throwable $error) => get_class($error) . ": " . $error->getMessage(), $errors);
        $this->assertContains(MessageException::class . ": Cannot parse", $messages);
        $this->assertContains(\RuntimeException::class . ": Handler failed", $messages);
    }
}
