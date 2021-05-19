<?php

namespace Prokl\ServiceProvider\Extra\Contract;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Interface ExtraFeatureServiceProviderInterface
 * @package Prokl\ServiceProvider\Extra
 *
 * @since 20.03.2021
 */
interface ExtraFeatureServiceProviderInterface
{
    /**
     * @param ContainerBuilder $containerBuilder Контейнер.
     *
     * @return void
     */
    public function register(ContainerBuilder $containerBuilder) : void;
}