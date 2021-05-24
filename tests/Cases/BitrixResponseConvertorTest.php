<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Bitrix\Main\HttpResponse;
use Prokl\BitrixTestingTools\Base\BitrixableTestCase;
use Prokl\ServiceProvider\Services\PSR\BitrixResponseConvertor;

/**
 * Class BitrixResponseConvertorTest
 */
class BitrixResponseConvertorTest extends BitrixableTestCase
{
    /**
     * @var BitrixResponseConvertor $obTestObject
     */
    protected $obTestObject;

    /**
     * @var HttpResponse $bitrixResponse
     */
    private $bitrixResponse;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->bitrixResponse = $this->getBitrixResponse();
        $this->obTestObject = new BitrixResponseConvertor($this->bitrixResponse);
    }

    /**
     * @return void
     */
    public function testResponse() : void
    {
        $result = $this->obTestObject->response();

        $headers = $result->headers->all();

        $this->assertNotEmpty($headers['cache-control']);
        $this->assertNotEmpty($headers['date']);

        $this->assertSame(200, $result->getStatusCode());
     }

    /**
     * Response из фикстуры.
     *
     * @return HttpResponse
     */
    private function getBitrixResponse() : HttpResponse
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/response.json');

        return unserialize($fixture);
    }
}