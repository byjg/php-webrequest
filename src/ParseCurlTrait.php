<?php


namespace ByJG\Util;


use ByJG\Util\Exception\RequestException;
use ByJG\Util\Psr7\MemoryStream;
use ByJG\Util\Psr7\Response;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;

trait ParseCurlTrait
{
    /**
     * @param string $body
     * @param $curlHandle
     * @param bool $close
     * @return Response|MessageInterface
     */
    public function parseCurl(string $body, $curlHandle, bool $close = true): Response
    {
        try {
            $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
            $status = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            $effectiveUrl = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
            if ($close) {
                curl_close($curlHandle);
            }

            $response = Response::getInstance($status)
                ->withBody(new MemoryStream(substr($body, $headerSize)))
                ->withHeader("X-Effective-Url", $effectiveUrl);

            return $this->parseHeader($response, substr($body, 0, $headerSize));
        } catch (\Exception $ex) {
            throw new RequestException($this->request, $ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    /**
     * @param ResponseInterface $response
     * @param string $rawHeaders
     * @return Response
     */
    protected function parseHeader(ResponseInterface $response, string $rawHeaders): ResponseInterface
    {
        foreach (preg_split("/\r?\n/", $rawHeaders) as $headerLine) {
            $headerLine = explode(':', $headerLine, 2);

            if (isset($headerLine[1])) {
                $response = $response->withHeader($headerLine[0], preg_replace("/^\s+/", "", $headerLine[1]));
            }
        }

        return $response;
    }

}