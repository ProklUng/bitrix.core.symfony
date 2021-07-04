<?php

namespace Prokl\ServiceProvider\Tests\Cases\CompilerPass;

use Exception;
use Prokl\ServiceProvider\CompilePasses\TwigExtensionTaggedServicesPass;
use Prokl\TestingTools\Base\BaseTestCase;
use Symfony\Component\DependencyInjection\Compiler\RemoveUnusedDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TwigExtensionTaggedServicesPassTest
 * @package Prokl\ServiceProvider\Tests\Cases\CompilerPass
 * @coversDefaultClass TwigExtensionTaggedServicesPass
 *
 * @since 12.10.2020
 */
class TwigExtensionTaggedServicesPassTest extends BaseTestCase
{
    /**
     * @var TwigExtensionTaggedServicesPass $obTestObject Тестируемый объект.
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->obTestObject = new TwigExtensionTaggedServicesPass();
    }

    /**
     * process(). Нормальный ход событий.
     *
     * @return void
     * @throws Exception
     */
    public function testProcess(): void
    {
        $testClass = $this->getStubService();
        $testContainerBuilder = $this->getTestContainer('test.service', $testClass, true);

        $this->obTestObject->process(
            $testContainerBuilder
        );

        $param = $testContainerBuilder->getParameter('_twig_extension');
        $this->assertNotEmpty(
            $param,
            'Сервис не обработался.'
        );
    }

    /**
     * process(). Без тэгированного сервиса.
     *
     * @return void
     * @throws Exception
     */
    public function testProcessWithoutTaggedService(): void
    {
        $testClass = $this->getStubService();
        $testContainerBuilder = $this->getTestContainer('test.service', $testClass, false);

        $this->obTestObject->process(
            $testContainerBuilder
        );

        $this->assertFalse(
            $testContainerBuilder->hasParameter('_twig_extension'),
            'Сервис проскочил.'
        );
     }

    /**
     * Мок обработчика.
     *
     * @return mixed
     */
    private function getStubService()
    {
        return new class  {
        };
    }

    /**
     * Тестовый контейнер.
     *
     * @param string      $serviceId ID сервиса.
     * @param object|null $object
     * @param boolean     $tagged
     *
     * @return ContainerBuilder
     */
    private function getTestContainer(
        string $serviceId,
        ?object $object = null,
        bool $tagged = true
    ): ContainerBuilder {

        if ($object === null) {
            $object = $this->getStubService();
        }

        $container = new ContainerBuilder();

        if ($tagged) {
            $container
                ->register($serviceId, get_class($object))
                ->addTag('twig.extension')
                ->setPublic(true);
        } else {
            $container
                ->register($serviceId, get_class($object))
                ->setPublic(true);
        }

        $this->process($container);

        return $container;
    }

    /**
     * @param ContainerBuilder $container Контейнер.
     */
    private function process(ContainerBuilder $container): void
    {
        (new RemoveUnusedDefinitionsPass())->process($container);
    }
}
