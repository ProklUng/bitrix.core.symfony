<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Bitrix\Main\HttpRequest;
use Prokl\BitrixTestingTools\Base\BitrixableTestCase;
use Prokl\ServiceProvider\Services\PSR\BitrixRequestConvertor;

/**
 * Class BitrixRequestConvertorTest
 */
class BitrixRequestConvertorTest extends BitrixableTestCase
{
    // use ResetDatabaseTrait;

    /**
     * @var BitrixRequestConvertor $obTestObject
     */
    protected $obTestObject;

    /**
     * @var HttpRequest $bitrixRequest
     */
    private $bitrixRequest;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->bitrixRequest = $this->getBitrixRequest();
        $this->obTestObject = new BitrixRequestConvertor($this->bitrixRequest);
    }

    /**
     * @return void
     */
    public function testRequest() : void
    {
        $result = $this->obTestObject->request();

        $query = $result->query->all();

        $this->assertNotEmpty($query['clear_cache']);
        $this->assertNotEmpty($query['query']);
        $this->assertSame('123', $query['query']);

        $server = $result->server->all();

        $this->assertNotEmpty($server['HTTP_HOST']);
        $this->assertSame('bitrix-example2.loc', $server['HTTP_HOST']);

        $this->assertNotEmpty($server['HTTP_X_REAL_IP']);
        $this->assertSame('127.0.0.1', $server['HTTP_X_REAL_IP']);

        $this->assertNotEmpty($server['HTTP_COOKIE']);

        $cookies = $result->cookies->all();
        $this->assertNotEmpty($cookies);
    }

    /**
     * Request из фикстуры.
     *
     * @return HttpRequest
     */
    private function getBitrixRequest() : HttpRequest
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/request.json');

        return unserialize($fixture);
    }
}