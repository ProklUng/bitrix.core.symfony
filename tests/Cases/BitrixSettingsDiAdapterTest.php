<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Exception;
use Prokl\ServiceProvider\Tests\Cases\fixtures\Services\SampleService;
use Prokl\ServiceProvider\Tests\Cases\fixtures\Services\SampleWithArguments;
use Prokl\ServiceProvider\Utils\BitrixSettingsDiAdapter;
use Prokl\TestingTools\Base\BaseTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class BitrixSettingsDiAdapterTest
 * @package Cases
 *
 * @since 12.07.2021
 */
class BitrixSettingsDiAdapterTest extends BaseTestCase
{
    /**
     * @var BitrixSettingsDiAdapter $obTestObject
     */
    protected $obTestObject;

    /**
     * @var ContainerBuilder $containerBuilder Контейнер.
     */
    private $containerBuilder;

    /**
     * @var array $fixture Фикстура.
     */
    private $fixture;

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new BitrixSettingsDiAdapter();
        $this->containerBuilder = new ContainerBuilder();
        $this->fixture = $this->loadFixture();
    }

    /**
     * Импорт параметров напрямую.
     *
     * @return void
     */
    public function testImportParams() : void
    {
        $this->obTestObject->importParameters(
            $this->containerBuilder,
            $this->fixture['rabbitmq']['value']
        );

        $this->assertFalse($this->containerBuilder->hasParameter('rabbitmq'));

        $this->assertTrue($this->containerBuilder->hasParameter('connections'));
        $this->assertIsArray($this->containerBuilder->getParameter('connections'));

        $this->assertTrue($this->containerBuilder->hasParameter('producers'));
        $this->assertIsArray($this->containerBuilder->getParameter('producers'));

        $this->assertTrue($this->containerBuilder->hasParameter('consumers'));
        $this->assertIsArray($this->containerBuilder->getParameter('consumers'));
    }

    /**
     * Импорт параметров в секцию контейнера.
     *
     * @return void
     */
    public function testImportParamsToSection() : void
    {
        $this->obTestObject->importParameters(
            $this->containerBuilder,
            $this->fixture['rabbitmq']['value'],
            'rabbitmq'
        );

        $this->assertTrue($this->containerBuilder->hasParameter('rabbitmq'));
        $this->assertFalse($this->containerBuilder->hasParameter('connections'));
        $this->assertFalse($this->containerBuilder->hasParameter('producers'));
        $this->assertFalse($this->containerBuilder->hasParameter('consumers'));
    }

    /**
     * Импорт сервисов.
     *
     * @return void
     * @throws Exception
     */
    public function testImportServices() : void
    {
        $this->fixture = $this->loadFixture('/.settings_services.php');

        $this->obTestObject->importServices(
            $this->containerBuilder,
            $this->fixture['services']['value']
        );

        $this->assertTrue($this->containerBuilder->has('foo.service'));
        $this->assertInstanceOf(
            SampleService::class,
            $this->containerBuilder->get('foo.service'),
            'Не тот класс сервиса.'
        );

        $this->assertTrue($this->containerBuilder->has('someGoodServiceName'));
        $service = $this->containerBuilder->get('someGoodServiceName');
        $this->assertInstanceOf(
            SampleWithArguments::class,
            $service,
            'Не тот класс сервиса.'
        );

        $this->assertSame('foo', $service->getFoo(), 'Параметр foo не пробросился.');
        $this->assertSame('bar', $service->getBar(), 'Параметр bar не пробросился.');

        $this->assertTrue($this->containerBuilder->has('someModule.someServiceName'));
        $service = $this->containerBuilder->get('someModule.someServiceName');
        $this->assertInstanceOf(
            SampleWithArguments::class,
            $service,
            'Не тот класс сервиса.'
        );

        $this->assertSame('foo', $service->getFoo(), 'Параметр foo не пробросился.');
        $this->assertSame('bar', $service->getBar(), 'Параметр bar не пробросился.');
    }

    /**
     * Импорт сервисов.
     *
     * @return void
     * @throws Exception
     */
    public function testImportServicesClosures() : void
    {
        $this->fixture = $this->loadFixture('/.settings_services.php');

        $this->obTestObject->importServices(
            $this->containerBuilder,
            $this->fixture['services']['value']
        );

        $this->assertTrue($this->containerBuilder->has('someModule.someAnotherServiceName'));
        $service = $this->containerBuilder->get('someModule.someAnotherServiceName');
        $this->assertInstanceOf(
            SampleWithArguments::class,
            $service,
            'Не тот класс сервиса.'
        );

        $this->assertSame('foo', $service->getFoo(), 'Параметр foo не пробросился.');
        $this->assertSame('bar', $service->getBar(), 'Параметр bar не пробросился.');
    }

        /**
     * @param string $file Файл с фикстурой.
     *
     * @return array
     */
    private function loadFixture(string $file = '/.settings.php') : array
    {
        $path = __DIR__ . '/../Fixtures/Settings';

        return include $path . $file;
    }
}