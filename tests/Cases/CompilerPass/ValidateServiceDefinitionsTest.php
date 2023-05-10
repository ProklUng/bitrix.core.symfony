<?php

namespace Prokl\ServiceProvider\Tests\Cases\CompilerPass;

use Exception;
use Prokl\ServiceProvider\CompilePasses\ValidateServiceDefinitions;
use Prokl\TestingTools\Base\BaseTestCase;
use Symfony\Component\DependencyInjection\Compiler\RemoveUnusedDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class BootstrapServicesTest
 * @package Prokl\ServiceProvider\Tests\Cases\CompilerPass
 * @coversDefaultClass ValidateServiceDefinitions
 *
 * @since 04.07.2021.
 */
class ValidateServiceDefinitionsTest extends BaseTestCase
{
    /**
     * @var ValidateServiceDefinitions $obTestObject Тестируемый объект.
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

        $this->obTestObject = new ValidateServiceDefinitions();
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
        $testContainerBuilder = $this->getTestContainer('test_service');

        $this->obTestObject->process($testContainerBuilder);

        $this->assertTrue(true);
    }

    /**
     * process(). Абстрактный класс.
     *
     * @return void
     * @throws Exception
     *
     */
    public function testProcessAbstract(): void
    {
        $testContainerBuilder = $this->getTestContainer('test_service', null, true);

        $this->obTestObject->process($testContainerBuilder);

        $this->assertTrue(true);
    }

    /**
     * process(). Несуществующий класс.
     *
     * @return void
     * @throws Exception
     *
     */
    public function testProcessInvalidClass(): void
    {
        $testContainerBuilder = $this->getTestContainer('test_service', FakeClass::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Service test_service is configured to use the nonexistent class Prokl\ServiceProvider\Tests\Cases\CompilerPass\FakeClass'
        );

        $this->obTestObject->process($testContainerBuilder);
    }

    /**
     * process(). Сервисы-исключения.
     *
     * @param string $serviceId ID сервиса.
     *
     * @return void
     * @throws Exception
     *
     * @dataProvider dataProviderKnownServices
     */
    public function testProcessKnownServices(string $serviceId): void
    {
        $testContainerBuilder = $this->getTestContainer($serviceId, null, true);

        $this->obTestObject->process($testContainerBuilder);

        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public function dataProviderKnownServices() : array
    {
        return [
            ['beberlei_metrics.util.buzz.curl'],
            ['beberlei_metrics.util.buzz.browser'],
            ['form.type.entity'],
            ['debug.file_link_formatter.url_format'],
            ['service_container']
        ];
    }

    /**
     * Мок обработчика.
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
     * @param string      $serviceId ID сервиса.
     * @param string|null $class     Класс сервиса.
     * @param boolean     $abstract  Абстрактный сервис.
     *
     * @return ContainerBuilder
     */
    private function getTestContainer(
        string $serviceId,
        ?string $class = null,
        bool $abstract = false
    ): ContainerBuilder {
        $container = new ContainerBuilder();
        $container
            ->register($serviceId, $class ?? get_class($this->stubService))
            ->setAbstract($abstract)
            ->setPublic(true);

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
