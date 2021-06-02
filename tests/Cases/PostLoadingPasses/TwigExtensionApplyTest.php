<?php

namespace Prokl\ServiceProvider\Tests\Cases\PostLoadingPasses;

use Prokl\ServiceProvider\PostLoadingPass\TwigExtensionApply;
use Prokl\TestingTools\Base\BaseTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TwigExtensionApplyTest
 * @package Tests\ServiceProvider\PostLoadingPasses
 * @coversDefaultClass TwigExtensionApply
 *
 * @since 12.10.2020
 */
class TwigExtensionApplyTest extends BaseTestCase
{
    /**
     * @var TwigExtensionApply $obTestObject Тестируемый объект.
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->obTestObject = new TwigExtensionApply();
    }

    /**
     * action(). Нормальный ход событий.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @return void
     */
    public function testActionNormal(): void
    {
        $testContainer = $this->getTestContainer('test.service', new class () {
        }, true);

        $testContainer->set('twig.instance', new class {
                public function addExtension($extension){}
            });
            
        $result = $this->obTestObject->action($testContainer);

        $this->assertTrue(
            $result,
            'Процесс не прошел.'
        );
    }

    /**
     * action(). Нет сервиса.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @return void
     */
    public function testActionNoService(): void
    {
        $testContainer = $this->getTestContainer('test.service', new class () {
        }, false);

        $result = $this->obTestObject->action($testContainer);

        $this->assertFalse(
            $result,
            'Процесс не прошел.'
        );
    }

    /**
     * Тестовый контейнер.
     *
     * @param string     $serviceId ID сервиса.
     * @param mixed|null $object
     * @param boolean    $tagged
     *
     * @return ContainerBuilder
     */
    private function getTestContainer(
        string $serviceId,
        $object = null,
        bool $tagged = true
    ): ContainerBuilder {

        $container = new ContainerBuilder();

        if ($tagged) {
            $container
                ->register($serviceId, get_class($object))
                ->addTag('twig.extension')
                ->setPublic(true);

            $container->setParameter(
                '_twig_extension',
                [$serviceId => []]
            );
        } else {
            $container
                ->register($serviceId, get_class($object))
                ->setPublic(true);
        }

        return $container;
    }
}
