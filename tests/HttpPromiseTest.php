<?php

namespace Tests;

use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\NetworkException;
use ByJG\WebRequest\HttpClient;
use ByJG\WebRequest\HttpPromise;
use ByJG\WebRequest\HttpPromiseState;
use ByJG\WebRequest\MockClient;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Request;
use ByJG\WebRequest\Psr7\Response;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class HttpPromiseTest extends TestCase
{
    protected string $server;

    #[\Override]
    public function setUp(): void
    {
        $host = empty(getenv('HTTP_TEST_HOST')) ? "localhost" : getenv('HTTP_TEST_HOST');
        $port = empty(getenv('HTTP_TEST_PORT')) ? "8080" : getenv('HTTP_TEST_PORT');

        $this->server = "http://$host:$port";
    }

    protected function request(string $path, string $query = ''): Request
    {
        return Request::getInstance(Uri::getInstanceFromString($this->server . $path)->withQuery($query));
    }

    public function testSendAsync(): void
    {
        $promise = HttpClient::getInstance()->sendAsync($this->request('/rest.php', 'a=1'));
        $this->assertEquals(HttpPromiseState::Pending, $promise->getState());

        $response = $promise->await();
        $this->assertEquals(HttpPromiseState::Fulfilled, $promise->getState());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['a' => '1'], json_decode((string)$response->getBody(), true)['query_string']);
    }

    public function testAllRunsInParallelAndKeepsTheOrder(): void
    {
        $httpClient = HttpClient::getInstance();
        $start = microtime(true);

        // Each request takes 2 seconds: the slower first one would come last in the order they finish
        $all = HttpPromise::all(
            first: $httpClient->sendAsync($this->request('/multirequest.php', 'param=1')),
            second: $httpClient->sendAsync($this->request('/multirequest.php', 'param=2')),
            third: $httpClient->sendAsync($this->request('/multirequest.php', 'param=3')),
        );
        $bodies = array_map(fn(ResponseInterface $response) => (string)$response->getBody(), $all->await());

        $this->assertEquals(['first' => '1', 'second' => '2', 'third' => '3'], $bodies);
        // In sequence it would take 6 seconds
        $this->assertLessThan(4, microtime(true) - $start);
    }

    public function testThenChainsAnotherRequest(): void
    {
        $httpClient = HttpClient::getInstance();

        // A promise returned by then() is followed, as in JavaScript
        $result = $httpClient->sendAsync($this->request('/rest.php', 'next=2'))
            ->then(fn(ResponseInterface $response) => $httpClient->sendAsync(
                $this->request('/multirequest.php', 'param=' . json_decode((string)$response->getBody(), true)['query_string']['next'])
            ))
            ->then(fn(ResponseInterface $response) => (string)$response->getBody())
            ->await();

        $this->assertEquals('2', $result);
    }

    public function testNetworkErrorRejects(): void
    {
        // Nothing listens on the port 1
        $promise = HttpClient::getInstance()->sendAsync(Request::getInstance(Uri::getInstanceFromString('http://localhost:1/')));

        try {
            $promise->await();
            $this->fail('await() did not throw the rejection');
        } catch (NetworkException $ex) {
            $this->assertStringStartsWith('CURL - ', $ex->getMessage());
            $this->assertEquals('http://localhost:1/', (string)$ex->getRequest()->getUri());
        }
        $this->assertEquals(HttpPromiseState::Rejected, $promise->getState());

        $recovered = HttpClient::getInstance()
            ->sendAsync(Request::getInstance(Uri::getInstanceFromString('http://localhost:1/')))
            ->catch(fn(NetworkException $ex) => 'recovered');
        $this->assertEquals('recovered', $recovered->await());
    }

    public function testAllRejectsWithTheFirstRejection(): void
    {
        $httpClient = HttpClient::getInstance();

        $all = HttpPromise::all(
            $httpClient->sendAsync($this->request('/multirequest.php', 'param=1')),
            $httpClient->sendAsync(Request::getInstance(Uri::getInstanceFromString('http://localhost:1/'))),
        );

        $start = microtime(true);
        $this->expectException(NetworkException::class);
        try {
            $all->await();
        } finally {
            // It does not wait for the slow request
            $this->assertLessThan(1.5, microtime(true) - $start);
        }
    }

    public function testRace(): void
    {
        $httpClient = HttpClient::getInstance();

        $response = HttpPromise::race(
            $httpClient->sendAsync($this->request('/multirequest.php', 'param=slow')),
            $httpClient->sendAsync($this->request('/rest.php', 'param=fast')),
        )->await();

        $this->assertEquals(['param' => 'fast'], json_decode((string)$response->getBody(), true)['query_string']);
    }

    public function testThenCatchFinally(): void
    {
        $calls = [];

        $value = HttpPromise::resolve(5)
            ->then(fn($value) => $value * 2)
            ->then(fn($value) => throw new RuntimeException("failed with $value"))
            ->then(function () use (&$calls) {
                $calls[] = 'skipped';
            })
            ->catch(fn(RuntimeException $ex) => $ex->getMessage())
            ->finally(function () use (&$calls) {
                $calls[] = 'finally';
                return 'ignored';
            })
            ->await();

        $this->assertEquals('failed with 10', $value);
        $this->assertEquals(['finally'], $calls);
    }

    public function testThenWithOnRejected(): void
    {
        $promise = HttpPromise::reject(new RuntimeException('reason'));

        $this->assertEquals(
            'handled reason',
            $promise->then(fn() => 'not called', fn(RuntimeException $ex) => 'handled ' . $ex->getMessage())->await()
        );

        // Without a handler the reason passes through, and finally keeps it
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reason');
        $promise->then(fn() => 'not called')->finally(fn() => null)->await();
    }

    public function testFinallyThrowingRejects(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('finally failed');
        HttpPromise::resolve(1)->finally(fn() => throw new LogicException('finally failed'))->await();
    }

    public function testCallbacksAddedAfterSettlingRun(): void
    {
        $promise = HttpPromise::resolve(1);
        $this->assertEquals(HttpPromiseState::Fulfilled, $promise->getState());
        $this->assertEquals(2, $promise->then(fn($value) => $value + 1)->await());
    }

    public function testTry(): void
    {
        $promise = HttpPromise::try(fn(int $a, int $b) => $a + $b, 2, 3);
        $this->assertEquals(HttpPromiseState::Fulfilled, $promise->getState());
        $this->assertEquals(5, $promise->await());

        // A returned promise is followed
        $this->assertEquals('inner', HttpPromise::try(fn() => HttpPromise::resolve('inner'))->await());
    }

    public function testTryRejectsWhatItThrows(): void
    {
        // try() does not throw
        $promise = HttpPromise::try(fn() => throw new RuntimeException('failed'));
        $this->assertEquals(HttpPromiseState::Rejected, $promise->getState());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('failed');
        $promise->await();
    }

    public function testAllEmpty(): void
    {
        $this->assertEquals([], HttpPromise::all()->await());
    }

    public function testAwaitAPromiseThatCannotSettle(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The promise cannot settle: there is no request in progress');
        (new HttpPromise())->await();
    }

    public function testMockClientSendAsync(): void
    {
        $expected = (new Response(201))->withBody(new MemoryStream('mocked'));
        $mock = new MockClient($expected);

        $promise = $mock->sendAsync($this->request('/rest.php'));
        $this->assertEquals(HttpPromiseState::Fulfilled, $promise->getState());
        $this->assertSame($expected, $promise->await());
        $this->assertEquals($this->server . '/rest.php', (string)$mock->getRequestedObject()->getUri());
    }
}
