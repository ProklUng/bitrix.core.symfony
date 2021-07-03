<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use InvalidArgumentException;
use Prokl\ServiceProvider\Bundles\BundlesLoader;
use Prokl\ServiceProvider\Tests\Fixtures\DummyService;
use Prokl\ServiceProvider\Tests\Fixtures\TestingBundle;
use Prokl\ServiceProvider\Tests\Fixtures\TestingBundleDev;
use Prokl\TestingTools\Base\BaseTestCase;
use Prokl\TestingTools\Tools\PHPUnitUtils;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class BundlesLoaderTest
 * @package Prokl\ServiceProvider\Tests\Cases
 *
 * @since 01.06.2021
 * @since 03.07.2021 Актуализация.
 */
class BundlesLoaderTest extends BaseTestCase
{
    /**
     * @var BundlesLoader $obTestObject
     */
    protected $obTestObject;

    /**
     * @var ContainerBuilder $dummyContainer
     */
    private $dummyContainer;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['DOCUMENT_ROOT'] = __DIR__;
        $_ENV['DEBUG'] = true;

        $this->dummyContainer = new ContainerBuilder();
        $this->obTestObject = new BundlesLoader(
            $this->dummyContainer,
            'dev',
            '/../Fixtures/bundles.php'
        );
    }

    /**
     * load(). Нормальный ход вещей.
     *
     * @return void
     */
    public function testLoad() : void
    {
        $this->obTestObject->load();

        $result = $this->obTestObject->bundles();

        $this->assertCount(1, $result);
        $this->assertSame('TestingBundle', array_key_first($result));
        $this->assertInstanceOf(TestingBundle::class, $result['TestingBundle']);
        // Не загрузился ли бандл для другого окружения.
        $this->assertArrayNotHasKey(TestingBundleDev::class, $result);
    }

    /**
     * load(). Несуществующий конфиг.
     *
     * @return void
     */
    public function testLoadDefaultPath() : void
    {
        $this->obTestObject = new BundlesLoader(
            $this->dummyContainer,
            'dev',
            '/../Fixtures/fake.php' // Несуществующий конфиг
        );

        $this->obTestObject->load();

        $result = $this->obTestObject->bundles();

        $this->assertEmpty($result);
    }

    /**
     * load(). Другое окружение.
     *
     * @return void
     */
    public function testLoadAnotherEnv() : void
    {
        $this->obTestObject = new BundlesLoader(
            $this->dummyContainer,
            'test',
            '/../Fixtures/bundles.php'
        );

        $this->obTestObject->load();

        $result = $this->obTestObject->bundles();

        $this->assertArrayHasKey('TestingBundle', $result);
        $this->assertArrayHasKey('TestingBundleDev', $result);
        $this->assertInstanceOf(TestingBundleDev::class, $result['TestingBundleDev']);
    }

    /**
     * load(). Бандл без метода RegisterExtension.
     *
     * @return void
     */
    public function testLoadWithoutRegisterExtension() : void
    {
        $this->obTestObject = new BundlesLoader(
            $this->dummyContainer,
            'dev',
            '/../Fixtures/invalid_bundles.php'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Bundle Prokl\ServiceProvider\Tests\Fixtures\TestingInvalidBundle dont have implemented getContainerExtension method.'
        );

        $this->obTestObject->load();
    }

    /**
     * load(). Invalid class.
     *
     * @return void
     */
    public function testLoadInvalidClass() : void
    {
        $this->obTestObject = new BundlesLoader(
            $this->dummyContainer,
            'dev',
            '/../Fixtures/fake_bundles.php'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bundle class Prokl\ServiceProvider\Tests\Fixtures\FakeBundle not exist.');

        $this->obTestObject->load();
    }

    /**
     * boot(). Проверяется, что в класс бандла загоняется полноценный контейнер.
     * И, что у бандла вызван метод boot.
     *
     * @return void
     * @throws ReflectionException Ошибки рефлексии.
     */
    public function testBoot() : void
    {
        $this->obTestObject->load();

        $this->dummyContainer->register('test.service', DummyService::class);
        $this->obTestObject->boot($this->dummyContainer);

        $result = $this->obTestObject->bundles();
        $bundle = current($result);

        $container = PHPUnitUtils::getProtectedProperty(
            $bundle,
            'container'
        );

        $this->assertTrue(
            $container->has('test.service'),
            'Контейнер не обработался до конца. Ожидаемого сервиса нет.'
        );

        $this->assertTrue(
            $bundle->booted,
            'Метод boot бандла не вызывался.'
        );
    }

    /**
     * getBundlesMap().
     *
     * @return void
     */
    public function testGetBundlesMap() : void
    {
        $this->obTestObject->load();
        $result = $this->obTestObject::getBundlesMap();

        $this->assertCount(1, $result);
        $this->assertSame('TestingBundle', array_key_first($result));
        $this->assertInstanceOf(TestingBundle::class, $result['TestingBundle']);
    }

    /**
     * bootAfterCompilingContainer().
     *
     * @return void
     */
    public function testBootAfterCompilingContainer() : void
    {
        $this->obTestObject->load();
        $result = $this->obTestObject::getBundlesMap();
        $bundle = get_class(current($result));

        $this->dummyContainer->setParameter('kernel.bundles', [
            $bundle
        ]);

        $this->obTestObject::bootAfterCompilingContainer($this->dummyContainer);

        $this->assertTrue(
            $bundle::$booted_static,
            'Метод boot не запускался.'
        );
    }
}
