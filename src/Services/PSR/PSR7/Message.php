<?php


namespace Prokl\ServiceProvider\Services\PSR\PSR7;

use Bitrix\Main\HttpRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Message
 * @package Prokl\ServiceProvider\Services\PSR\PSR7
 */
class Message implements MessageInterface
{
    const DEFAULT_HTTP_VERSION = '1.1';

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var string
     */
    protected $httpVersion;

    /**
     * @var mixed|null
     */

    protected $body;
    /**
     * @var UriInterface
     */

    protected $uri;
    /**
     * @var array
     */

    protected $attributes;

    /**
     * Message constructor.
     *
     * @param HttpRequest $request
     * @param string|null $httpVersion
     * @param mixed       $body
     * @param array       $attributes
     */
    public function __construct(
        HttpRequest $request,
        string $httpVersion = null,
        $body = null,
        array $attributes = []
    ){
        $this->request = $request;
        $this->httpVersion = $httpVersion;
        $this->body = $body;
        if (empty($this->body) && $this->needCheckBody($request)) {
            $rawInput = fopen('php://input', 'r');
            $tempStream = fopen('php://temp', 'r+');
            stream_copy_to_stream($rawInput, $tempStream);
            rewind($tempStream);
            $this->body = Utils::streamFor($tempStream);
        }
        $this->uri = new Uri($this->getCurrentLink());
        $this->attributes = $attributes;
    }

    /**
     * @param HttpRequest $request
     *
     * @return boolean
     */
    private function needCheckBody(HttpRequest $request)
    {
        $method = strtolower($request->getRequestMethod());
        return in_array($method, ['post', 'put']);
    }

    private function getCurrentLink()
    {
        $server = $this->request->getServer();
        return ($server->get('HTTPS') === 'on' ? "https" : "http").
            "://".
            $server->get('HTTP_HOST').
            $server->get('REQUEST_URI');
    }

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        if (!empty($this->httpVersion)) {
            return $this->httpVersion;
        }

        $version = $this->request->getServer()->get('SERVER_PROTOCOL') ?? static::DEFAULT_HTTP_VERSION;
        return $this->httpVersion = str_replace(['HTTP', '/'], '', $version);
    }


    /**
     * @param string $version
     * @return $this|Message
     */
    public function withProtocolVersion($version)
    {
        return new static($this->request, $version, $this->body, $this->attributes);
    }

    /**
     * @return array|string[][]
     */
    public function getHeaders()
    {
        $headers = $this->request->getHeaders()->toArray();
        foreach ($headers as &$value) {
            $value = (array)($value ?? []);
        }
        unset($value);

        return $headers;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return !empty($this->getHeader($name));
    }

    public function getHeader($name)
    {
        return (array)($this->request->getHeader($name) ?? []);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name)
    {
        $value = $this->getHeader($name);
        if (empty($value)) {
            return '';
        }

        return implode(',', (array)$value);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return $this|Message
     */
    public function withHeader($name, $value)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->add($name, $value);
        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return $this|Message
     */
    public function withAddedHeader($name, $value)
    {
        if ($this->hasHeader($name)) {
            return $this;
        }

        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->add($name, $value);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @param string $name
     * @return $this|Message
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->delete($name);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return StreamInterface
     */
    public function getBody()
    {
        if (!$this->body) {
            $this->body = Utils::streamFor('');
        }

        return $this->body;
    }

    /**
     * @param StreamInterface $body
     * @return $this|Message
     */
    public function withBody(StreamInterface $body)
    {
        if ($body === $this->body) {
            return $this;
        }

        return new static($this->request, $this->httpVersion, $body, $this->attributes);
    }
}
