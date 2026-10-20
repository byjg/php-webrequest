<?php

namespace ByJG\WebRequest;

use ByJG\WebRequest\Exception\CurlException;
use ByJG\WebRequest\Exception\NetworkException;
use CurlHandle;
use CurlMultiHandle;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * The curl_multi handle shared by every HttpClient::sendAsync(): the requests run in parallel
 * in the current process while HttpPromise::await() calls tick().
 *
 * @internal
 */
class CurlMultiLoop
{
    use ParseCurlTrait;

    protected static ?CurlMultiLoop $instance = null;

    protected CurlMultiHandle $multiHandle;

    /** @var array<int, array{CurlHandle, RequestInterface, HttpPromise}> The transfers in progress by handle id */
    protected array $transfers = [];

    public static function getInstance(): CurlMultiLoop
    {
        if (self::$instance === null) {
            self::$instance = new CurlMultiLoop();
        }
        return self::$instance;
    }

    protected function __construct()
    {
        $this->multiHandle = curl_multi_init();
    }

    /**
     * Start the transfer of the handle and return the promise of its response
     *
     * @throws CurlException
     */
    public function add(CurlHandle $curlHandle, RequestInterface $request): HttpPromise
    {
        $promise = new HttpPromise();
        $this->transfers[spl_object_id($curlHandle)] = [$curlHandle, $request, $promise];
        curl_multi_add_handle($this->multiHandle, $curlHandle);

        // Starts connecting without waiting
        $this->exec();

        return $promise;
    }

    /**
     * Transfer the pending requests, waiting up to one second for activity, and settle the promises
     * of the finished ones.
     *
     * @return bool False when there is no request in progress
     * @throws CurlException
     */
    public function tick(): bool
    {
        if (count($this->transfers) === 0) {
            return false;
        }

        if (curl_multi_select($this->multiHandle, 1.0) === -1) {
            usleep(1000);
        }
        $this->exec();

        return true;
    }

    /**
     * @throws CurlException
     */
    protected function exec(): void
    {
        do {
            $status = curl_multi_exec($this->multiHandle, $running);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        if ($status !== CURLM_OK) {
            throw new CurlException('CURL multi - ' . curl_multi_strerror($status));
        }

        while (($info = curl_multi_info_read($this->multiHandle)) !== false) {
            $this->finish($info['handle'], $info['result']);
        }
    }

    /**
     * The then/catch/finally callbacks run here and may add new requests
     */
    protected function finish(CurlHandle $curlHandle, int $result): void
    {
        $id = spl_object_id($curlHandle);
        [, $request, $promise] = $this->transfers[$id];
        unset($this->transfers[$id]);

        $body = curl_multi_getcontent($curlHandle);
        curl_multi_remove_handle($this->multiHandle, $curlHandle);

        if ($result !== CURLE_OK) {
            $promise->settle(
                HttpPromiseState::Rejected,
                new NetworkException($request, 'CURL - ' . (curl_error($curlHandle) ?: curl_strerror($result)))
            );
            return;
        }

        try {
            $response = $this->parseCurl((string)$body, $curlHandle);
        } catch (Throwable $ex) {
            $promise->settle(HttpPromiseState::Rejected, $ex);
            return;
        }
        $promise->fulfill($response);
    }
}
