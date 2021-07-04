<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use CMain;
use Mockery;
use Prokl\ServiceProvider\Utils\ErrorScreen;
use Prokl\TestingTools\Base\BaseTestCase;
use RuntimeException;

/**
 * Class ErrorScreenTest
 * @package Prokl\ServiceProvider\Tests\Cases
 * @coversDefaultClass ErrorScreen
 *
 * @since 04.07.2021
 */
class ErrorScreenTest extends BaseTestCase
{
    /**
     * @var ErrorScreen $obTestObject
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new ErrorScreen($this->getMockCmain());
    }

    /**
     * die().
     *
     * @return void
     */
    public function testDie() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test');
        $this->obTestObject->die('Test');
    }

    /**
     * Мок CMain.
     *
     * @return mixed
     */
    private function getMockCmain()
    {
        return Mockery::mock(CMain::class);
    }
}
