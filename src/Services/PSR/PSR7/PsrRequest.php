<?php

namespace Prokl\ServiceProvider\Services\PSR\PSR7;

use Bitrix\Main\HttpRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class PsrRequest
 * @package Prokl\ServiceProvider\Services\PSR\PSR7
 */
class PsrRequest extends Message implements RequestInterface
{
    /**
     * @return string
     */
    public function getRequestTarget()
    {
        return (string)$this->request->getRequestUri();
    }

    /**
     * @param mixed $requestTarget
     *
     * @return $this|PsrRequest
     */
    public function withRequestTarget($requestTarget)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_URI', $requestTarget);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return string|null
     */
    public function getMethod()
    {
        return $this->request->getRequestMethod();
    }

    /**
     * @param string $method
     *
     * @return $this|PsrRequest
     */
    public function withMethod($method)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_METHOD', $method);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return UriInterface
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     * @param false $preserveHost
     * @return $this|PsrRequest
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getServer()->set('REQUEST_URI', $uri);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @return HttpRequest
     */
    protected function getClonedRequest()
    {
        return clone $this->request;
    }
}
