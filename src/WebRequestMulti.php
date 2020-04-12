<?php

namespace ByJG\Util;

use ByJG\Util\Psr7\Request;
use Psr\Http\Message\RequestInterface;

class WebRequestMulti
{
    /**
     * @var array
     */
    protected $curlClients = [];

    /**
     * @var HttpClient
     */
    protected $httpClient = null;

    /**
     * @var \Closure
     */
    protected $defaultOnSuccess = null;

    /**
     * @var \Closure
     */
    protected $defaultOnError = null;

    public function __construct(HttpClient $httpClient, \Closure $defaultOnSuccess = null, \Closure $defaultOnError = null)
    {
        $this->httpClient = $httpClient;

        $this->defaultOnSuccess = $defaultOnSuccess;
        if (is_null($defaultOnSuccess)) {
            $this->defaultOnSuccess = function () {
                return;
            };
        }

        $this->defaultOnError = $defaultOnError;
        if (is_null($defaultOnError)) {
            $this->defaultOnError = function () {
                return;
            };
        }
    }

    /**
     * @param RequestInterface $request
     * @param \Closure|null $onSuccess
     * @param \Closure|null $onError
     * @return $this
     */
    public function addRequest(
        RequestInterface $request,
        \Closure $onSuccess = null,
        \Closure $onError = null
    ) {
        if (is_null($onSuccess)) {
            $onSuccess = $this->defaultOnSuccess;
        }

        if (is_null($onError)) {
            $onError = $this->defaultOnError;
        }

        $data = new \stdClass();
        $data->request = $request;
        $data->onSuccess = $onSuccess;
        $data->onError = $onError;
        $data->handle = null;

        $this->curlClients[] = $data;

        return $this;
    }

    /**
     * @param HttpClient $httpClient
     * @throws CurlException
     */
    public function execute()
    {
        // multi handle
        $multiInitHandle = curl_multi_init();

        // loop through $data and create curl handles
        // then add them to the multi-handle
        foreach ($this->curlClients as $id => $object) {
            $object->handle = $this->httpClient->createCurlHandle($object->request);
            $this->curlClients[$id] = $object;
            curl_multi_add_handle($multiInitHandle, $object->handle);
        }

        // execute the handles
        $running = null;
        do {
            $status = curl_multi_exec($multiInitHandle, $running);

            // Check status
            switch ($status) {
                case CURLM_BAD_HANDLE:
                    throw new CurlException('Bad Handle');

                case CURLM_BAD_EASY_HANDLE:
                    throw new CurlException('Bad Easy Handle');

                case CURLM_OUT_OF_MEMORY:
                    throw new CurlException('Out of memory');

                case CURLM_INTERNAL_ERROR:
                    throw new CurlException('Internal Error');

            }
        } while ($running > 0);

        // get content and remove handles
        $errorList = [];
        foreach ($this->curlClients as $id => $object) {
            $body = curl_multi_getcontent($object->handle);
            $error = curl_error($object->handle);
            if (!empty($error)) {
                curl_multi_remove_handle($multiInitHandle, $object->handle);
                $closure = $object->onError;
                try {
                    $closure($error, $id);
                } catch (\Exception $ex) {
                    $errorList[] = $ex;
                }
                continue;
            }
            
            $headerSize = curl_getinfo($object->handle, CURLINFO_HEADER_SIZE);
            $closure = $object->onSuccess;
            
            try {
                $closure(substr($body, $headerSize), $id);
            } catch (\Exception $ex) {
                $errorList[] = $ex;
            }
            
            curl_multi_remove_handle($multiInitHandle, $object->handle);
        }

        // all done
        curl_multi_close($multiInitHandle);
        
        if (count($errorList) > 0) {
            throw $errorList[0];
        }
    }
}
