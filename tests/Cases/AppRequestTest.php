<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Prokl\ServiceProvider\Services\AppRequest;
use Prokl\TestingTools\Base\BaseTestCase;
use Prokl\TestingTools\Tools\PHPUnitUtils;
use ReflectionException;

/**
 * Class AppRequest
 * @package Prokl\ServiceProvider\Tests\Cases
 * @coversDefaultClass AppRequest
 *
 * @since 24.12.2020 Новые тесты.
 */
class AppRequestTest extends BaseTestCase
{
    /**
     * @var AppRequest $obTestObject
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/';

        $this->obTestObject = new AppRequest();
    }

    /**
     * getDocumentRoot().
     *
     * @return void
     */
    public function testGetDocumentRoot() : void
    {
        $result = $this->obTestObject->getDocumentRoot();

        $this->assertSame(
            $_SERVER['DOCUMENT_ROOT'],
            $result,
            'Неправильный DOCUMENT_ROOT.'
        );
    }

    /**
     * getHttpHost().
     *
     * @return void
     */
    public function testGetHttpHost() : void
    {
        $result = $this->obTestObject->getHttpHost();

        $this->assertSame(
            $_SERVER['HTTP_HOST'],
            $result,
            'Неправильный HTTP_HOST.'
        );
    }

    /**
     * initGlobals().
     *
     * @return void
     * @throws ReflectionException
     */
    public function testGetSuperglobal() : void
    {
        $backupGET = $_GET;
        $_GET['test'] = 'Y';

        PHPUnitUtils::callMethod(
            $this->obTestObject,
            'initGlobals'
        );

        $this->assertSame(
            'Y',
            $_GET['test'],
            'Суперглобалы не обработались.'
        );

        $_GET = $backupGET;
    }

    /**
     * initGlobals(). Empty values.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testGetSuperglobalEmpty() : void
    {
        $backupGET = $_GET;
        $_GET = null;

        PHPUnitUtils::callMethod(
            $this->obTestObject,
            'initGlobals'
        );

        $this->assertIsArray(
            $_GET,
            'Суперглобалы не обработались.'
        );

        $_GET = $backupGET;
    }

    /**
     * getRequestUri().
     *
     * @return void
     */
    public function testGetRequestUri() : void
    {
        $result = $this->obTestObject->getRequestUri();

        $this->assertSame(
            $_SERVER['REQUEST_URI'],
            $result,
            'Неправильный REQUEST_URI.'
        );
    }

    /**
     * setServer().
     *
     * @return void
     */
    public function testSetServer() : void
    {
        $key = 'TEST.PHP.UNIT';
        $value = 'OK';

        $this->obTestObject->setServer($key, $value);

        $request = $this->obTestObject->getRequest();

        $this->assertSame(
            $value,
            $request->server->get($key),
            'Неправильная установка ключа $_REQUEST.'
        );
    }
}
