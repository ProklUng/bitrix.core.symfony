<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Exception;
use Prokl\BitrixTestingTools\Base\BitrixableTestCase;
use Prokl\ServiceProvider\ServiceProvider;
use Prokl\ServiceProvider\Services\AppKernel;
use ReflectionProperty;
use RuntimeException;

/**
 * Class ServiceProviderTest
 * @package Prokl\ServiceProvider\Tests
 *
 * @since 02.06.2021
 *
 */
class ServiceProviderTest extends BitrixableTestCase
{
    /**
     * @var ServiceProvider
     */
    protected $obTestObject;

    /**
     * @var string $pathYamlConfig Путь к конфигу.
     */
    private $pathYamlConfig = '../../../../tests/Fixtures/config/test_container.yaml';

    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        parent::setUp();

        $_ENV['DEBUG'] = true;

        if (!@file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/configs')) {
            @mkdir($_SERVER['DOCUMENT_ROOT'] . '/local/configs', 0777, true);
        }

        $this->rrmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/s1/containers');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV['DEBUG'], $_ENV['APP_DEBUG'], $_ENV['APP_ENV']);
    }

    /**
     * @return void
     * @throws Exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoad() : void
    {
        $_ENV['DEBUG'] = true;

        $this->obTestObject = new ServiceProvider(
            $this->pathYamlConfig
        );

        $container = $this->obTestObject->container();

        $this->assertTrue($container->has('kernel'), 'Kernel не зарегистрировался');
        $this->assertTrue($container->has('test_service'), 'Тестовый сервис не зарегистрировался');
    }

    /**
     *
     * Обработка переменных окружения.
     *
     * @param boolean $debug
     * @param boolean $appDebug
     * @param string  $env
     * @param string  $expectedResult
     *
     * @return void
     * @throws Exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @dataProvider dataProviderDebugEnv
     */
    public function testEnvironments(bool $debug, bool $appDebug, string $env, string $expectedResult) : void
    {
        $_ENV['DEBUG'] = $debug;
        $_ENV['APP_DEBUG'] = $appDebug;
        $_ENV['APP_ENV'] = $env;

        $this->obTestObject = new ServiceProvider($this->pathYamlConfig);

        /** @var AppKernel $kernel */
        $kernel = $this->obTestObject->get('kernel');
        $kernelParams = $kernel->getKernelParameters();

        $this->assertSame($appDebug, $kernelParams['kernel.debug'], 'kernel.debug установился неправильно.');
        $this->assertSame(
            $expectedResult,
            $kernelParams['kernel.environment'],
            'kernel.environment установился неправильно.'
        );
    }

    /**
     * @return array[]
     */
    public function dataProviderDebugEnv() : array
    {
        return [
          [false, true, 'dev', 'dev'],
          [true, true, 'test', 'test'],
          [false, true, 'test', 'test'],
          [true, false, 'prod', 'prod'],
        ];
    }

    /**
     *
     * shutdown().
     *
     * @return void
     * @throws Exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShutdown(): void
    {
        $this->obTestObject = new ServiceProvider($this->pathYamlConfig);

        /** @var AppKernel $kernel */
        $kernel = $this->obTestObject->get('kernel');

        $this->obTestObject->shutdown();

        $reflection = new ReflectionProperty(ServiceProvider::class, 'containerBuilder');
        $reflection->setAccessible(true);
        $value = $reflection->getValue(null);

        $this->assertNull($value, 'Контейнер не обнулился');
        $this->assertNull($kernel->getContainer(), 'Контейнер в kernel не обнулился');
    }

    /**
     *
     * reboot().
     *
     * @return void
     * @throws Exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReboot(): void
    {
        $this->obTestObject = new ServiceProvider($this->pathYamlConfig);

        $this->obTestObject->reboot();

        /** @var AppKernel $kernel */
        $kernel = $this->obTestObject->get('kernel');

        $reflection = new ReflectionProperty(ServiceProvider::class, 'containerBuilder');
        $reflection->setAccessible(true);
        $value = $reflection->getValue(null);

        $this->assertNotNull($value, 'Контейнер обнулился');
        $this->assertNotNull($kernel->getContainer(), 'Контейнер в kernel обнулился');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoadInvalidConfigFile() : void
    {
        $_ENV['DEBUG'] = true;

        $this->expectException(RuntimeException::class);
        $this->obTestObject = new ServiceProvider(
            '/fake.yaml'
        );
    }

    /**
     * Компилируется ли контейнер?
     *
     * @return void
     * @throws Exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoadProd() : void
    {
        $_ENV['DEBUG'] = false;

        $this->obTestObject = new ServiceProvider(
            $this->pathYamlConfig,
        );

        $container = $this->obTestObject->container();

        $this->assertTrue($container->has('kernel'));
        $this->assertTrue($container->has('test_service'));
        $this->assertTrue(file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/s1/containers'));

        $this->rrmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/s1/containers');
    }

    /**
     * Грузятся ли бандлы?
     *
     * @return void
     * @throws Exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoadBundles() : void
    {
        $_ENV['DEBUG'] = true;

        $this->obTestObject = new ServiceProvider(
            $this->pathYamlConfig,
            '/../../../../tests/Fixtures/bundles.php'
        );

        $container = $this->obTestObject->container();

        $this->assertTrue($container->has('kernel'), 'Kernel не зарегистрировался');
        $this->assertTrue($container->has('test_service'), 'Тестовый сервис не зарегистрировался');

        $bundles = $container->getParameter('kernel.bundles');

        $this->assertSame(
            ['TestingBundle' => 'Prokl\ServiceProvider\Tests\Fixtures\TestingBundle'],
            $bundles,
            'Бандл не загрузился.'
        );

        $bundlesMeta = $container->getParameter('kernel.bundles_metadata');
        $this->assertNotEmpty($bundlesMeta);
    }

    /**
     * Рекурсивно удалить папку со всем файлами и папками.
     *
     * @param string $dir Директория.
     *
     * @return void
     */
    private function rrmdir(string $dir) : void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (filetype($dir. '/' .$object) === 'dir') {
                        $this->rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir. '/' . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
