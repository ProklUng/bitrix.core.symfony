<?php

namespace Prokl\ServiceProvider\Tests\Cases\PostLoadingPasses;

use Exception;
use Prokl\ServiceProvider\PostLoadingPass\BootstrapServices;
use Prokl\TestingTools\Base\BaseTestCase;
use Symfony\Component\DependencyInjection\Compiler\RemoveUnusedDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class BootstrapServicesTest
 * @package Prokl\ServiceProvider\Tests\Cases\PostLoadingPasses
 * @coversDefaultClass BootstrapServices
 *
 * @since 28.09.2020
 */
class BootstrapServicesTest extends BaseTestCase
{
    /**
     * @var BootstrapServices $obTestObject Тестируемый объект.
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new BootstrapServices();
    }

    /**
     * action(). Нормальный ход событий.
     *
     * @return void
     * @throws Exception
     */
    public function testAction(): void
    {
        $testContainerBuilder = $this->getTestContainer('service.bootstrap');

        $result = $this->obTestObject->action(
            $testContainerBuilder
        );

        $this->assertTrue(
            $result,
            'Что-то пошло не так.'
        );
    }

    /**
     * action(). Нет обработчиков. Пустой parameterBag.
     *
     * @return void
     * @throws Exception
     */
    public function testActionNoListener(): void
    {
        $container = $this->getEmptyContainer();

        $result = $this->obTestObject->action(
            $container
        );

        $this->assertFalse(
            $result,
            'Что-то пошло не так.'
        );
    }

    /**
     * Мок обработчика.
     *
     * @return mixed
     */
    private function getStubService()
    {
        return new class {
            public function addEvent(): void
            {
            }
        };
    }

    /**
     * Тестовый контейнер.
     *
     * @param string $serviceId ID сервиса.
     * @param array  $params    Параметры.
     *
     * @return ContainerBuilder
     */
    private function getTestContainer(
        string $serviceId,
        array $params = [
            ['event' => 'test'],
        ]
    ): ContainerBuilder {
        $container = new ContainerBuilder();
        $container
            ->register($serviceId, get_class($this->getStubService()))
            ->setPublic(true);

        $container->setParameter('_bootstrap', [
            $serviceId => $params,
        ]);

        $this->process($container);

        return $container;
    }

    /**
     * Пустой контейнер.
     *
     * @return ContainerBuilder
     */
    private function getEmptyContainer() : ContainerBuilder
    {
        return new ContainerBuilder();
    }

    /**
     * @param ContainerBuilder $container Контейнер.
     */
    private function process(ContainerBuilder $container): void
    {
        (new RemoveUnusedDefinitionsPass())->process($container);
    }
}
