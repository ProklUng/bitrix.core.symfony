<?php

namespace Prokl\ServiceProvider\CompilePasses\TagPass;

use LogicException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class TargetedTagPass
 * @package Prokl\ServiceProvider\CompilePasses
 *
 * @since 07.11.2020
 * @since 08.11.2020 Сортировка по приоритету.
 */
class TargetedTagPass extends AbstractTagPass implements CompilerPassInterface
{
    /**
     * @var string $method Метод.
     */
    private $method;

    /**
     * @var string $tag Собираемый тэг.
     */
    private $tag;

    /**
     * TargetedTagPass constructor.
     *
     * @param string $tag    Собираемый тэг.
     * @param string $method Метод.
     */
    public function __construct(string $tag, string $method)
    {
        $this->tag = $tag;
        $this->method = $method;
    }

    /**
     * Движуха.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     *
     * @since 08.11.2020 Сортировка по приоритету.
     */
    public function process(ContainerBuilder $container) : void
    {
        $this->container = $container;

        $taggedServices = $this->container->findTaggedServiceIds($this->tag);
        // Сортировка по приоритету.
        $taggedServices = $this->sortByPriority($taggedServices);

        foreach ($taggedServices as $taggedServiceId => $tags) {
            foreach ($tags as $attributes) {
                $this->processAttributes($attributes, $taggedServiceId);
            }
       }
    }

    /**
     * Обработать аттрибуты.
     *
     * @param array  $attributes      Аттрибуты.
     * @param string $taggedServiceId Тагированный ID сервиса.
     *
     * @return void
     * @throws LogicException
     *
     * @since 08.11.2020 Сортировка по приоритету.
     */
    private function processAttributes(array $attributes, string $taggedServiceId) : void
    {
        if (isset($attributes['service'], $attributes['tag'])) {
            throw new LogicException(
                'Tagged service (' . $taggedServiceId . ') can only contain either a service or a tag, in tag "' . $this->tag . '"'
            );
        }

        if (isset($attributes['service'])) {
            $this->addCall($attributes['service'], $this->method, $taggedServiceId);
            return;
        }

        $taggedServices = $this->container->findTaggedServiceIds($attributes['tag']);
        // Сортировка по приоритету.
        $taggedServices = $this->sortByPriority($taggedServices);

        if (isset($attributes['tag'])) {
            foreach ($taggedServices as $targetServiceId => $tags) {
                $this->addCall($targetServiceId, $this->method, $taggedServiceId);
            }

            return;
        }

        throw new LogicException(
            'Tagged service (' . $taggedServiceId . ') should contain at least a service or a tag in  tag "' .
            $this->tag . '"'
        );
    }
}
