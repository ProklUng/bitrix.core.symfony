<?php

namespace Prokl\ServiceProvider\Utils;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class BitrixSettingsDiAdapter
 * @package Prokl\ServiceProvider\Utils
 *
 * @since 12.07.2021
 */
class BitrixSettingsDiAdapter
{
    /**
     * Импортировать параметры из .settings.php
     *
     * @param ContainerBuilder $container Контейнер.
     * @param array            $settings  Секция parameters .settings.php.
     * @param string|null      $section   Если задано, то параметры попадут в отдельную секцию контейнера.
     *
     * @return void
     */
    public function importParameters(
        ContainerBuilder $container,
        array $settings,
        ?string $section = null
    ): void {
        if ($section !== null) {
            $container->setParameter($section, $settings);

            return;
        }

        foreach ($settings as $id => $value) {
            $container->setParameter($id, $value);
        }
    }

    /**
     * Импортировать сервисы из .settings.php.
     *
     * @param ContainerBuilder $container Контейнер.
     * @param array            $services  Секция services .settings.php.
     *
     * @return void
     */
    public function importServices(ContainerBuilder $container, array $services): void
    {
        foreach ($services as $id => $service) {
            // Ключ ignore в описании сервиса (дабы не попадал в общий контейнер)
            if (array_key_exists('ignore', $service)
                &&
                $service['ignore'] === true
            ) {
                continue;
            }

            // Если такой сервис уже есть - игнор.
            if ($container->hasDefinition($id)) {
                continue;
            }

            if (array_key_exists('constructor', $service)
                &&
                is_callable($service['constructor'])
            ) {
                $definition = $container->register($id, FactoryClosure::class);
                $definition->setFactory([FactoryClosure::class, 'from']);
                $definition->addArgument($service['constructor']);
                $definition->setPublic(true);
            }

            if (array_key_exists('className', $service) && is_string($service['className'])) {
                $definition = $container->register($id, $service['className'])->setPublic(true);

                if (array_key_exists('constructorParams', $service) && is_callable($service['constructorParams'])) {
                    $arguments = $service['constructorParams']();
                    if (is_array($arguments)) {
                        foreach ($arguments as $argument) {
                            $definition->addArgument($argument);
                        }
                    } else {
                        $definition->addArgument($service['constructorParams']());
                    }
                }

                if (array_key_exists('constructorParams', $service) && is_array($service['constructorParams'])) {
                    foreach ($service['constructorParams'] as $param) {
                        $definition->addArgument($param);
                    }
                }

                // Тэги
                if (array_key_exists('tags', $service) && array_key_exists('name', $service['tags'])) {
                    $tagName = $service['tags']['name'];
                    unset($service['tags']['name']);

                    $definition->addTag(
                        $tagName,
                        $service['tags']
                    );
                }
            }
        }
    }
}
