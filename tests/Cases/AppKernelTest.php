<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Prokl\BitrixTestingTools\Base\BitrixableTestCase;
use Prokl\ServiceProvider\Bundles\BundlesLoader;
use Prokl\ServiceProvider\Services\AppKernel;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ServiceProviderTest
 * @package Prokl\ServiceProvider\Tests
 *
 * @since 05.07.2021
 *
 */
class AppKernelTest extends BitrixableTestCase
{
    /**
     * @var AppKernel $obTestObject
     */
    protected $obTestObject;

    /**
     * Загрузка бандлов по кастомному пути.
     *
     * @return void
     */
    public function testLoadBundlesFromCustomPath() : void
    {
        $container = new ContainerBuilder();

        $bundlesLoader = new BundlesLoader(
            $container,
            'dev',
            '/../../../../tests/Fixtures/bundles.php'
        );
        $bundlesLoader->load();

        $this->obTestObject = new AppKernel('dev', true);
        $result = $this->obTestObject->getBundlesMetaData();

        $this->assertNotEmpty($result['kernel.bundles']);
        $this->assertNotEmpty($result['kernel.bundles_metadata']);
    }

    /**
     * getRequestUri().
     *
     * @return void
     * @throws ReflectionException
     */
    public function testGetRequestUri() : void
    {
        $this->goTo('/test/');

        $this->obTestObject = new AppKernel('dev', true);

        $result = $this->obTestObject->getRequestUri();

        $this->assertSame('/test/', $result);
    }
}
