<?php

namespace Prokl\ServiceProvider\Micro;

use Prokl\ServiceProvider\Bundles\BundlesLoader;

/**
 * Class BundlesLoader
 * @package Prokl\ServiceProvider\Micro
 * Загрузчик бандлов.
 *
 * @since 24.10.2020
 * @since 08.11.2020 Устранение ошибки, связанной с многократной загрузкой конфигурации бандлов.
 * @since 19.11.2020 Сделать все приватные подписчики событий публичными.
 * @since 20.12.2020 Сделать все приватные консольные команды публичными.
 */
class BundlesLoaderMicroKernel extends BundlesLoader
{
    /**
     * @var array $bundlesMap Инициализированные классы бандлов.
     */
    protected static $bundlesMap = [];

}
