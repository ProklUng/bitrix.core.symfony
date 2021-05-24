<?php

namespace Prokl\ServiceProvider\Services\PSR;

use Bitrix\Main\HttpResponse;
use Prokl\ServiceProvider\Services\PSR\PSR7\PsrResponse;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BitrixResponseConvertor
 * @package Prokl\ServiceProvider\Services\PSR
 *
 * @since 24.05.2021
 */
class BitrixResponseConvertor
{
    /**
     * @var HttpResponse $bitrixResponse Битриксовый Response.
     */
    private $bitrixResponse;

    /**
     * @var PsrResponse $psrResponse
     */
    private $psrResponse;

    /**
     * BitrixResponseConvertor constructor.
     *
     * @param HttpResponse $bitrixResponse Битриксовый Response.
     */
    public function __construct(HttpResponse $bitrixResponse)
    {
        $this->bitrixResponse = $bitrixResponse;
        $this->psrResponse = new PsrResponse($this->bitrixResponse);
    }

    /**
     * Response.
     *
     * @return Response
     */
    public function response() : Response
    {
        $httpFoundationFactory = new HttpFoundationFactory();

        return $httpFoundationFactory->createResponse($this->psrResponse);
    }

    /**
     * Битриксовый Request, приведенный к PSR-7.
     *
     * @return PsrResponse
     */
    public function psrResponse(): PsrResponse
    {
        return $this->psrResponse;
    }
}