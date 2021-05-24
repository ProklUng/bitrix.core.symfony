<?php

namespace Prokl\ServiceProvider\Services\PSR\PSR7;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\HttpResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Serializable;

/**
 * Class PsrResponse
 * @package Prokl\ServiceProvider\Services\Convertor
 *
 * @psalm-consistent-constructor
 */
class PsrResponse implements ResponseInterface, Serializable
{
    private const DEFAULT_HTTP_VERSION = '1.1';

    /**
     * @var HttpResponse $response Битриксовый Response.
     */
    private $response;

    /**
     * @var string $httpVersion
     */
    private $httpVersion;

    /**
     * @var mixed|null $body
     */
    private $body;

    /**
     * PsrResponse constructor.
     *
     * @param HttpResponse $response    Битриксовый Response.
     * @param string|null  $httpVersion HTTP version.
     * @param string|null  $body        Тело ответа.
     */
    public function __construct(HttpResponse $response, ?string $httpVersion = null, ?string $body = '')
    {
        $this->response = $response;
        $this->httpVersion = $httpVersion ?? static::DEFAULT_HTTP_VERSION;
        $this->body = $body;
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->httpVersion;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {
        return new static($this->response, $version, $this->body);
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->response->getHeaders()->toArray();
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        return !empty($this->getHeader($name));
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        return $this->response->getHeaders()->get($name, true);
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        $value = $this->getHeader($name);
        if (empty($value)) {
            return '';
        }

        return implode(',', $value);
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $newResponse = clone $this->response;
        $newResponse->getHeaders()->set($name, $value);

        return new static($newResponse, $this->httpVersion, $this->body);
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        if ($this->hasHeader($name)) {
            return $this;
        }

        return $this->withHeader($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $newResponse = clone $this->response;
        $newResponse->getHeaders()->delete($name);
        return new static($newResponse, $this->httpVersion, $this->body);
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        if (!$this->body) {
            $this->body = Utils::streamFor($this->response->getContent());
        }

        return $this->body;
    }

    /**
     * @inheritDoc
     * @throws ArgumentTypeException
     */
    public function withBody(StreamInterface $body)
    {
        $newResponse = clone $this->response;
        $newResponse->setContent($body);

        return new static($newResponse, $this->httpVersion, $body);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode()
    {
        preg_match('/(\d+)\s+.*/', $this->response->getStatus(), $match);
        return (int)($match[1] ?? 200);
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $newResponse = clone $this->response;
        $newResponse->getHeaders()->set('Status', implode(' ', [$code, $reasonPhrase]));

        return new static($newResponse, $this->httpVersion, $this->body);
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase()
    {
        preg_match('/\d+\s+(.*)/', $this->response->getStatus(), $match);
        return $match[1] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize([
            'response' => $this->response,
            'http_version' => $this->httpVersion,
            'body' => (string)$this->body,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->response = $data['response'];
        $this->httpVersion = $data['http_version'];
        $this->body = $data['body'];
    }
}
