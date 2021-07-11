<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Exception;
use Prokl\BitrixTestingTools\Base\BitrixableTestCase;
use Prokl\ServiceProvider\Bundles\BundlesLoader;
use Prokl\ServiceProvider\Services\AppKernel;
use Prokl\ServiceProvider\Tests\Cases\fixtures\SampleService;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;

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

    /**
     * getContainer().
     *
     * @return void
     * @throws Exception
     */
    public function testGetContainer() : void
    {
        $container = $this->getTestContainer();

        $kernel = $container->get('kernel');
        $kernel->setContainer($container);

        $result = $kernel->getContainer()->get(SampleService::class);

        $this->assertInstanceOf(SampleService::class, $result);
    }

    /**
     * Тестовый локатор.
     *
     * @return ContainerInterface
     */
    private function getTestContainer()
    {
        $container = new ContainerBuilder();
        $container->setDefinition(SampleService::class, new Definition(SampleService::class))->setPublic(true);
        $container->register('kernel', AppKernel::class)
                ->setAutoconfigured(true)
                ->setPublic(true)
                ->setArguments(['dev', true]);

        $container->compile();

        return $container;
    }
}
