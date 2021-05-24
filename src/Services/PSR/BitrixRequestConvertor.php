<?php

namespace Prokl\ServiceProvider\Services\PSR;

use Bitrix\Main\HttpRequest;
use Prokl\ServiceProvider\Services\PSR\PSR7\PsrRequest;
use Prokl\ServiceProvider\Services\PSR\PSR7\ServerPsrRequest;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BitrixRequestConvertor
 * @package Prokl\ServiceProvider\Services\PSR
 *
 * @since 24.05.2021
 */
class BitrixRequestConvertor
{
    /**
     * @var HttpRequest $bitrixRequest Битриксовый Request.
     */
    private $bitrixRequest;

    /**
     * @var ServerPsrRequest $psrRequest
     */
    private $psrRequest;

    /**
     * BitrixRequestConvertor constructor.
     *
     * @param HttpRequest $bitrixRequest Битриксовый Request.
     */
    public function __construct(HttpRequest $bitrixRequest)
    {
        $this->bitrixRequest = $bitrixRequest;
        $this->psrRequest = new ServerPsrRequest($this->bitrixRequest);
    }

    /**
     * Request.
     *
     * @return Request
     */
    public function request() : Request
    {
        $httpFoundationFactory = new HttpFoundationFactory();

        return $httpFoundationFactory->createRequest($this->psrRequest);
    }

    /**
     * Битриксовый Request, приведенный к PSR-7.
     *
     * @return PsrRequest
     */
    public function psrRequest(): PsrRequest
    {
        return $this->psrRequest;
    }
}