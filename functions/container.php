<?php

use Prokl\ServiceProvider\ServiceProvider;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

if (!function_exists('container')) {
    /**
     * Экземпляр сервис-контейнера Symfony.
     *
     * @param string|object $classContainer Класс контейнера.
     *
     * @return ContainerInterface
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

    /**
     * Экземпляр манипулятора с делегированными контейнерами.
     *
     * @return null|object
     *
     * @throws Exception Ошибки инициализации контейнера.
     *
     * @since 30.07.2021
     */
    function delegatedContainer() : ?object
    {
        return container()->get('delegated_container_manipulator');
    }
}
