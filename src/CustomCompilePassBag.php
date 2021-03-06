<?php

namespace Prokl\ServiceProvider;

use Prokl\ServiceProvider\CompilePasses\TwigExtensionTaggedServicesPass;
use Prokl\ServiceProvider\CompilePasses\BaseAggregatedTaggedServicesPass;
use Prokl\ServiceProvider\CompilePasses\ContainerAwareCompilerPass;
use Prokl\ServiceProvider\PostLoadingPass\TwigExtensionApply;
use Prokl\ServiceProvider\CompilePasses\ValidateServiceDefinitions;
use Prokl\ServiceProvider\PostLoadingPass\BootstrapServices;
use Prokl\ServiceProvider\PostLoadingPass\InitBitrixEvents;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * Class CustomCompilePassBag
 * @package Prokl\ServiceProvider
 *
 * @since 28.09.2020
 * @since 19.12.2020 Подключение консольных команд.
 */
class CustomCompilePassBag
{
    /**
     * @var array $compilePassesBag Набор Compiler Passes.
     */
    private $compilePassesBag = [
        // Автозагрузка сервисов.
        [
            'pass' => BaseAggregatedTaggedServicesPass::class,
            'params' => [
                'service.bootstrap',
                '_bootstrap'
            ]
        ],
        // Инициализация событий через сервисные тэги.
        [
            'pass' => BaseAggregatedTaggedServicesPass::class,
            'params' =>
                ['bitrix.events.init', '_events'],
        ],

        // Инициализация кастомных типов свойств через сервисные тэги.
        [
            'pass' => BaseAggregatedTaggedServicesPass::class,
            'params' =>
                ['bitrix.property.type', '_custom_bitrix_property'],
        ],

        // Проверка классов сервисов на существование.
        [
            'pass' => ValidateServiceDefinitions::class,
            'phase' => PassConfig::TYPE_BEFORE_REMOVING
        ],

        // Автоматическая инжекция контейнера в сервисы, имплементирующие ContainerAwareInterface.
        [
            'pass' => ContainerAwareCompilerPass::class
        ],

        // Регистрация Twig extensions.
        [
            'pass' => TwigExtensionTaggedServicesPass::class
        ],

        // Подключение консольных команд.
        [
            'pass' => AddConsoleCommandPass::class,
            'phase' => PassConfig::TYPE_BEFORE_REMOVING
        ],
    ];

    /**
     * @var array $postLoadingPassesBag Пост-обработчики (PostLoadingPass) контейнера.
     */
    private $postLoadingPassesBag = [
        ['pass' => InitBitrixEvents::class, 'priority' => 10],
        ['pass' => BootstrapServices::class, 'priority' => 20],
        ['pass' => TwigExtensionApply::class, 'priority' => 20],
    ];

    /**
     * Compiler Passes.
     *
     * @return array|array[]
     */
    public function getCompilerPassesBag() : array
    {
        return $this->compilePassesBag;
    }

    /**
     * PostLoadingPasses.
     *
     * @return array[]|string[]
     */
    public function getPostLoadingPassesBag() : array
    {
        return $this->postLoadingPassesBag;
    }
}
