<?php

use Prokl\ServiceProvider\ServiceProvider;
use Symfony\Component\DependencyInjection\Container;

if (!function_exists('container')) {
    /**
     * Экземпляр сервис-контейнера Symfony.
     *
     * @param string|object $classContainer Класс контейнера.
     *
     * @return Container
     *
     * @since 21.03.2021 Класс (или объект) контейнера как параметр.
     */
    function container($classContainer = ServiceProvider::class)
    {
        $container = $classContainer::instance();
        if ($container === null) {
            throw new RuntimeException(
              'Service container '. is_object($classContainer) ? get_class($classContainer) : $classContainer.
                      ' not initialized.'
            );
        }

        return $container;
    }
}
