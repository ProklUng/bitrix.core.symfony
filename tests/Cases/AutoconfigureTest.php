<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Prokl\ServiceProvider\Framework\AutoconfigureConfig;
use Prokl\TestingTools\Base\BaseTestCase;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ObjectInitializerInterface;

/**
 * Class AutoconfigureTest
 * @package Prokl\ServiceProvider\Tests\Cases
 * @coversDefaultClass AutoconfigureConfig
 *
 * @since 26.06.2021
 */
class AutoconfigureTest extends BaseTestCase
{
    /**
     * @var AutoconfigureConfig $obTestObject
     */
    protected $obTestObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->obTestObject = new AutoconfigureConfig();
    }

    /**
     * getAutoConfigure(). Валидные классы.
     *
     * @return void
     */
    public function testGetAutoConfigureValidClasses() : void
    {
        $result = $this->obTestObject->getAutoConfigure();

        $this->assertArrayHasKey('controller.service_arguments', $result);
        $this->assertSame(AbstractController::class, $result['controller.service_arguments']);

        $this->assertArrayHasKey('controller.argument_value_resolver', $result);
        $this->assertSame(ArgumentValueResolverInterface::class, $result['controller.argument_value_resolver']);

        $this->assertArrayHasKey('container.service_locator', $result);
        $this->assertSame(ServiceLocator::class, $result['container.service_locator']);

        $this->assertArrayHasKey('kernel.event_subscriber', $result);
        $this->assertSame(EventSubscriberInterface::class, $result['kernel.event_subscriber']);

        $this->assertArrayHasKey('validator.constraint_validator', $result);
        $this->assertSame(ConstraintValidatorInterface::class, $result['validator.constraint_validator']);

        $this->assertArrayHasKey('validator.initializer', $result);
        $this->assertSame(ObjectInitializerInterface::class, $result['validator.initializer']);
    }

    /**
     * getAutoConfigure(). Несуществующий класс.
     *
     * @return void
     */
    public function testGetAutoConfigureNotValidClasses() : void
    {
        $this->obTestObject = new AutoconfigureConfig(
            [
                'fake.key' => Fake::class
            ]
        );

        $this->expectException(RuntimeException::class);
        $this->obTestObject->getAutoConfigure();
    }
}
