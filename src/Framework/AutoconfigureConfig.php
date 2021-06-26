<?php

namespace Prokl\ServiceProvider\Framework;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ObjectInitializerInterface;

/**
 * Class AutoconfigureConfig
 * @package Prokl\ServiceProvider\Framework
 *
 * @since 26.06.2021
 */
class AutoconfigureConfig
{
    /**
     * @var string[] $autoConfigure Автоконфигурация тэгов.
     */
    private $autoConfigure = [
        'controller.service_arguments' => AbstractController::class,
        'controller.argument_value_resolver' => ArgumentValueResolverInterface::class,
        'container.service_locator' => ServiceLocator::class,
        'kernel.event_subscriber' => EventSubscriberInterface::class,
        'validator.constraint_validator' => ConstraintValidatorInterface::class,
        'validator.initializer' => ObjectInitializerInterface::class,
    ];

    /**
     * AutoconfigureConfig constructor.
     *
     * @param string[] $autoConfigure Дополнительные конфигураторы для тэгов.
     *
     * @throws RuntimeException Когда необходимая зависимость не существует.
     */
    public function __construct(array $autoConfigure = [])
    {
        $this->autoConfigure = array_merge($this->autoConfigure, $autoConfigure);

        $this->checkDependency();
    }

    /**
     * Карта автоконфигурируемых тэгов.
     *
     * @return string[]
     */
    public function getAutoConfigure(): array
    {
        return $this->autoConfigure;
    }

    /**
     * Проверка на существование зависимостей.
     *
     * @return void
     * @throws RuntimeException Когда необходимая зависимость не существует.
     */
    private function checkDependency() : void
    {
        foreach ($this->autoConfigure as $class) {
            if (interface_exists($class)) {
                continue;
            }

            if (!class_exists($class)) {
                throw new RuntimeException(
                    'Need class ' . $class . ' not exist.'
                );
            }
        }
    }
}