<?php


namespace ByJG\Util;


use ByJG\Util\Psr7\Response;
use MintWare\Streams\MemoryStream;
use Psr\Http\Message\MessageInterface;

trait ParseCurlTrait
{
    /**
     * @param $body
     * @param $curlHandle
     * @param bool $close
     * @return Response|MessageInterface
     */
    public function parseCurl($body, $curlHandle, $close = true)
    {
        $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $lastFetchedUrl = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
        if ($close) {
            curl_close($curlHandle);
        }

        $response = Response::getInstance($status)
            ->withBody(new MemoryStream(substr($body, $headerSize)));

        $this->parseHeader($response, substr($body, 0, $headerSize));

        return $response;
    }

    /**
     * @param MessageInterface $response
     * @param $rawHeaders
     */
    protected function parseHeader(MessageInterface $response, $rawHeaders)
    {
        $key = '';

        foreach (preg_split("/\r?\n/", $rawHeaders) as $headerLine) {
            $headerLine = explode(':', $headerLine, 2);

            if (isset($headerLine[1])) {
                $response->withHeader($headerLine[0], preg_replace("/^\s+/", "", $headerLine[1]));
            }
        }
    }

}