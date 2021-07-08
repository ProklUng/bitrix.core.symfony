<?php

namespace Prokl\ServiceProvider\CompilePasses;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MakePrivateServicePublic
 * Сделать приватные сервисы публичными по заданному списку.
 * @package Prokl\ServiceProvider\CompilePasses
 *
 * @since 08.07.2021
 */
final class MakePrivateServicePublic implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container) : void
    {
        if (!$container->hasParameter('publicable_services')) {
            return;
        }

        $publicableServices = (array)$container->getParameter('publicable_services');

        $services = $container->getServiceIds();

        foreach ($services as $id => $service) {
            if (!$container->hasDefinition($service)
                ||
                !in_array($service, $publicableServices, true)
            ) {
                continue;
            }

            $def = $container->getDefinition($service);
            $def->setPublic(true);
        }
    }
}
