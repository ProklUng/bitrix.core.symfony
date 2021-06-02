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
    private $pathYamlConfig = '../Fixtures/config/test_container.yaml';

    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->rrmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/s1/containers');
        $_SERVER['DOCUMENT_ROOT'] = __DIR__;
    }

    /**
     * @inheritDoc
     */
    protected function tearDown() : void
    {
        parent::tearDown();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testLoad() : void
    {
        $_ENV['DEBUG'] = true;

        $this->obTestObject = new ServiceProvider(
            $this->pathYamlConfig
        );

        $container = $this->obTestObject->container();

        $this->assertTrue($container->has('kernel'));
        $this->assertTrue($container->has('test_service'));
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
     */
    public function testLoadBundles() : void
    {
        $_ENV['DEBUG'] = true;

        $this->obTestObject = new ServiceProvider(
            $this->pathYamlConfig,
            '/../Fixtures/bundles.php'
        );

        $container = $this->obTestObject->container();

        $this->assertTrue($container->has('kernel'));
        $this->assertTrue($container->has('test_service'));

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
