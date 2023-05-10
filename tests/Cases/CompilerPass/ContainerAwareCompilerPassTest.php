<?php

namespace Prokl\ServiceProvider\Tests\Cases\CompilerPass;

use Exception;
use Prokl\ServiceProvider\CompilePasses\ContainerAwareCompilerPass;
use Prokl\TestingTools\Base\BaseTestCase;
use Symfony\Component\DependencyInjection\Compiler\RemoveUnusedDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ContainerAwareCompilerPassTest
 * @package Prokl\ServiceProvider\Tests\Cases\CompilerPass
 * @coversDefaultClass ContainerAwareCompilerPass
 *
 * @since 28.09.2020
 */
class ContainerAwareCompilerPassTest extends BaseTestCase
{
    /**
     * @var ContainerAwareCompilerPass $obTestObject Тестируемый объект.
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new ContainerAwareCompilerPass();
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
        $testContainerBuilder = $this->getTestContainer('container.aware', $testClass);

        $this->obTestObject->process(
            $testContainerBuilder
        );

        $this->obTestObject = $testContainerBuilder->get('container.aware');
        $this->assertNotEmptyProtectedProp(
            'container',
            'Контейнер не заинжектился.'
        );
    }

    /**
     * process(). Не ContainerAware сервис.
     *
     * @return void
     * @throws Exception
     */
    public function testProcessOrdinaryService(): void
    {
        $testClass = $this->getStubServiceNoContainerAware();
        $testContainerBuilder = $this->getTestContainer('no.container.aware', $testClass);

        $this->obTestObject->process($testContainerBuilder);

        $this->obTestObject = $testContainerBuilder->get('no.container.aware');

        $result = $this->hasPropertyClass(
            $this->obTestObject,
            'container'
        );

        $this->assertFalse(
            $result
        );
    }

    /**
     * Мок обработчика.
     */
    private function getStubService()
    {
        return new class implements ContainerAwareInterface {
            use ContainerAwareTrait;
        };
    }

    /**
     * Мок обработчика.
     */
    private function getStubServiceNoContainerAware()
    {
        return new class {
        };
    }

    /**
     * Тестовый контейнер.
     *
     * @param string      $serviceId ID сервиса.
     * @param object|null $object    Сервис.
     *
     * @return ContainerBuilder
     */
    private function getTestContainer(
        string $serviceId,
        ?object $object = null
    ): ContainerBuilder {

        if ($object === null) {
            $object = $this->getStubService();
        }

        $container = new ContainerBuilder();
        $container
            ->register($serviceId, get_class($object))
            ->setPublic(true);

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

    /**
     * Есть ли переменная в классе?
     *
     * @param mixed  $object  Объект.
     * @param string $varName Переменная.
     *
     * @return boolean
     */
    private function hasPropertyClass($object, string $varName): bool
    {
        $arProps = get_class_vars(get_class($object));

        return array_key_exists($varName, $arProps);
    }
}
