<?php

namespace Prokl\ServiceProvider\Services\PSR\PSR18;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\HttpHeaders;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PsrClient
 * @package Prokl\ServiceProvider\Services\PSR\PSR18
 *
 * @see https://github.com/beta-eto-code/bitrix-psr18 (fork)
 */
class PsrClient implements ClientInterface
{
    /**
     * @var HttpClient $httpClient
     */
    private $httpClient;

    /**
     * Client constructor.
     *
     * @param HttpClient|null $httpClient
     */
    public function __construct(HttpClient $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new HttpClient();
    }

    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $bxClient = clone $this->httpClient;
        $this->loadHeaders($bxClient, $request);

        $body = (string)$request->getBody();
        if (empty($body)) {
            $body = null;
        }

        $bxClient->query($method, (string)$request->getUri(), $body);
        $responseBody = $bxClient->getResult();
        if (empty($responseBody)) {
            $responseBody = null;
        }

        return new Response($bxClient->getStatus(), $this->normalizeHeader($bxClient->getHeaders()), $responseBody);
    }


    /**
     * @param HttpClient $httpClient
     * @param RequestInterface $request
     */
    private function loadHeaders(HttpClient $httpClient, RequestInterface $request)
    {
        $httpClient->clearHeaders();
        foreach ($request->getHeaders() as $name => $values) {
            $httpClient->setHeader($name, implode(", ", $values));
        }
    }

    /**
     * @param HttpHeaders $headers
     * @return array
     */
    private function normalizeHeader(HttpHeaders $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[$key] = implode(", ", (array)$value);
        }

        return $result;
    }
}