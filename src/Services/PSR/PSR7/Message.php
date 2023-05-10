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
    protected const DEFAULT_HTTP_VERSION = '1.1';

    /**
     * @var HttpRequest $request
     */
    protected $request;

    /**
     * @var string|null $httpVersion
     */
    protected $httpVersion;

    /**
     * @var mixed|null
     */

    protected $body;

    /**
     * @var UriInterface $uri
     */
    protected $uri;

    /**
     * @var array $attributes
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
        ?string $httpVersion = null,
        $body = null,
        array $attributes = []
    ) {
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
     * @inheritDoc
     */
    public function getProtocolVersion() : string
    {
        if (!empty($this->httpVersion)) {
            return $this->httpVersion;
        }

        $version = $this->request->getServer()->get('SERVER_PROTOCOL') ?? static::DEFAULT_HTTP_VERSION;
        return $this->httpVersion = str_replace(['HTTP', '/'], '', $version);
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version) : MessageInterface
    {
        return new static($this->request, $version, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function getHeaders() : array
    {
        $headers = $this->request->getHeaders()->toArray();
        foreach ($headers as &$value) {
            $value = (array)($value ?? []);
        }
        unset($value);

        return $headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name) : bool
    {
        return !empty($this->getHeader($name));
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name) : array
    {
        return (array)($this->request->getHeader($name) ?? []);
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name) : string
    {
        $value = $this->getHeader($name);
        if (count($value) === 0) {
            return '';
        }

        return implode(',', $value);
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value) : MessageInterface
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->add($name, $value);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value) : MessageInterface
    {
        if ($this->hasHeader($name)) {
            return $this;
        }

        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->add($name, $value);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name) : MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $newRequest = $this->getClonedRequest();
        $newRequest->getHeaders()->delete($name);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function getBody() : StreamInterface
    {
        if (!$this->body) {
            $this->body = Utils::streamFor('');
        }

        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body) : MessageInterface
    {
        if ($body === $this->body) {
            return $this;
        }

        return new static($this->request, $this->httpVersion, $body, $this->attributes);
    }

    /**
     * @return HttpRequest
     */
    protected function getClonedRequest()
    {
        return clone $this->request;
    }

    /**
     * @param HttpRequest $request Битриксовый Request.
     *
     * @return boolean
     */
    private function needCheckBody(HttpRequest $request)
    {
        $method = strtolower($request->getRequestMethod());

        return in_array($method, ['post', 'put']);
    }

    /**
     * Текущий URL.
     *
     * @return string
     */
    private function getCurrentLink() : string
    {
        $server = $this->request->getServer();
        return ($server->get('HTTPS') === 'on' ? 'https' : 'http').
            '://'.
            $server->get('HTTP_HOST').
            $server->get('REQUEST_URI');
    }
}
