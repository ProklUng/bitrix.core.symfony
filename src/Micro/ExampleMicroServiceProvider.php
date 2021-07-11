<?php

namespace Prokl\ServiceProvider\Micro;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ExampleMicroServiceProvider
 * @package Prokl\ServiceProvider\Micro
 *
 * Пример микро-сервиспровайдера (для модулей и т.п.)
 *
 * @since 04.03.2021
 */
class ExampleMicroServiceProvider extends AbstractStandaloneServiceProvider
{
    /**
     * @var ContainerBuilder $containerBuilder Контейнер.
     */
    protected static $containerBuilder;

    /**
     * @var string $pathBundlesConfig Путь к конфигурации бандлов.
     */
    protected $pathBundlesConfig = '/src/SymfonyDI/Micro/example.config/standalone_bundles.php';

    /**
     * @var string $configDir Папка, где лежат конфиги.
     */
    protected $configDir = '/src/SymfonyDI/Micro/example.config/example.yaml';

    /**
     * @var string $kernelServiceClass Класс, реализующий сервис kernel.
     */
    protected $kernelServiceClass = ExampleAppKernel::class;
}