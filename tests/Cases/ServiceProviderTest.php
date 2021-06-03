<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Exception;
use Prokl\BitrixTestingTools\Base\BitrixableTestCase;
use Prokl\ServiceProvider\ServiceProvider;
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
