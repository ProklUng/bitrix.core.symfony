<?php

namespace Prokl\ServiceProvider\Tests\Cases\CompilerPass;

use Exception;
use Prokl\ServiceProvider\CompilePasses\MakePrivateServicePublic;
use Prokl\TestingTools\Base\BaseTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class MakePrivateServicePublicTest
 * @package Prokl\ServiceProvider\Tests\Cases\CompilerPass
 * @coversDefaultClass MakePrivateServicePublic
 *
 * @since 08.07.2021.
 */
class MakePrivateServicePublicTest extends BaseTestCase
{
    /**
     * @var MakePrivateServicePublic $obTestObject Тестируемый объект.
     */
    protected $obTestObject;

    /**
     * @var object $stubService Сервис.
     */
    private $stubService;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new MakePrivateServicePublic();
        $this->stubService = $this->getStubService();
    }

    /**
     * process(). Нормальный ход событий.
     *
     * @return void
     * @throws Exception
     *
     */
    public function testProcess(): void
    {
        $service = $this->getStubService();
        $testContainerBuilder = $this->getTestContainer(
            'test_service',
            get_class($service),
            ['test_service']
        );

        $this->obTestObject->process($testContainerBuilder);
        $testContainerBuilder->compile();

        $result = $testContainerBuilder->get('test_service');

        $this->assertInstanceOf(get_class($service), $result);
    }

    /**
     * process(). Пустой список сервисов, подлежащих обращению.
     *
     * @return void
     * @throws Exception
     *
     */
    public function testProcessEmptyParams(): void
    {
        $service = $this->getStubService();
        $testContainerBuilder = $this->getTestContainer(
            'test_service',
            get_class($service),
            []
        );

        $this->obTestObject->process($testContainerBuilder);
        $testContainerBuilder->compile();

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            'The "test_service" service or alias has been removed or inlined when the container was compiled. You should either make it public, or stop using the container directly and use dependency injection instead.'
        );

        $testContainerBuilder->get('test_service');
    }

    /**
     * process(). Сервис не в списке подлежащих обращению в public.
     *
     * @return void
     * @throws Exception
     *
     */
    public function testProcessNotInPublicableList(): void
    {
        $service = $this->getStubService();
        $testContainerBuilder = $this->getTestContainer(
            'test_service',
            get_class($service),
            ['test_service2']
        );

        $this->obTestObject->process($testContainerBuilder);
        $testContainerBuilder->compile();

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            'The "test_service" service or alias has been removed or inlined when the container was compiled. You should either make it public, or stop using the container directly and use dependency injection instead.'
        );

        $testContainerBuilder->get('test_service');
    }

    /**
     * Мок сервиса.
     */
    private function getStubService()
    {
        return new class {
            /**
             * @var boolean $running Признак - запускался ли сервис.
             */
            public $running = false;

            public function __construct()
            {
                $this->running = true;
            }

            public function addEvent(): void
            {
            }
        };
    }

    /**
     * Тестовый контейнер.
     *
     * @param string      $serviceId          ID сервиса.
     * @param string|null $class              Класс сервиса.
     * @param array       $publicableServices
     *
     * @return ContainerBuilder
     */
    private function getTestContainer(
        string $serviceId,
        ?string $class = null,
        array $publicableServices = []
    ): ContainerBuilder {
        $container = new ContainerBuilder();
        $container
            ->register($serviceId, $class ?? get_class($this->stubService))
            ->setPublic(false);

        $container->setParameter('publicable_services', $publicableServices);

        return $container;
    }
}
