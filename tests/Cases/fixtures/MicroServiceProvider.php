<?php

namespace Prokl\ServiceProvider\Tests\Cases\fixtures;

use Prokl\ServiceProvider\Micro\AbstractStandaloneServiceProvider;
use Prokl\ServiceProvider\Micro\ExampleAppKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MicroServiceProvider
 * @package Prokl\ServiceProvider\Tests\Fixtures
 *
 * Пример микро-сервиспровайдера (для модулей и т.п.)
 *
 * @since 11.07.2021
 */
class MicroServiceProvider extends AbstractStandaloneServiceProvider
{
    /**
     * @var ContainerBuilder $containerBuilder Контейнер.
     */
    protected static $containerBuilder;

    /**
     * @var string $pathBundlesConfig Путь к конфигурации бандлов.
     */
    protected $pathBundlesConfig = '/../../../../src/Micro/example.config/standalone_bundles.php';

    /**
     * @var string $configDir Папка, где лежат конфиги.
     */
    protected $configDir = '/../../../../src/Micro/example.config/example.yaml';

    /**
     * @var string $kernelServiceClass Класс, реализующий сервис kernel.
     */
    protected $kernelServiceClass = ExampleAppKernel::class;
}