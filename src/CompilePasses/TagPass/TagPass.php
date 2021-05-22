<?php

namespace Prokl\ServiceProvider\CompilePasses\TagPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class TagPass
 * @package Prokl\ServiceProvider\CompilePasses
 *
 * @since 07.11.2020
 * @since 08.11.2020 Сортировка по приоритету.
 */
class TagPass extends AbstractTagPass implements CompilerPassInterface
{
    /**
     * @var string $tag Собираемый тэг.
     */
    private $tag;

    /**
     * @var array $destinations Назначение.
     */
    private $destinations = [];

    /**
     * TagPass constructor.
     *
     * @param string $tag Собираемый тэг.
     */
    public function __construct(string $tag)
    {
        $this->tag = $tag;
    }

    /**
     * Добавить сервис назначения.
     *
     * @param string $serviceId ID сервиса.
     * @param string $method    Метод.
     *
     * @return $this
     */
    public function addServiceIdsTo(string $serviceId, string $method) : self
    {
        $this->destinations[] =[
            'id' => $serviceId,
            'method' => $method
        ];

        return $this;
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
        $taggedServices = $container->findTaggedServiceIds($this->tag);

        if (count($taggedServices) === 0) {
            return;
        }

        // Сортировка по приоритету.
        $taggedServices = $this->sortByPriority($taggedServices);

        foreach ($taggedServices as $taggedServiceId => $tags) {
           foreach ($this->destinations as $destination) {
               $this->addCall($destination['id'], $destination['method'], $taggedServiceId);
           }
       }
    }
}
