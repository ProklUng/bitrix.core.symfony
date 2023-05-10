<?php

namespace Prokl\ServiceProvider\Services\PSR\PSR7;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class PsrRequest
 * @package Prokl\ServiceProvider\Services\PSR\PSR7
 */
class PsrRequest extends Message implements RequestInterface
{
    /**
     * @inheritDoc
     */
    public function getRequestTarget() : string
    {
        return (string)$this->request->getRequestUri();
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget) : RequestInterface
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_URI', $requestTarget);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function getMethod() : string
    {
        return $this->request->getRequestMethod();
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method) : RequestInterface
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_METHOD', $method);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function getUri() : UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false) : RequestInterface
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_URI', $uri);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }
}
