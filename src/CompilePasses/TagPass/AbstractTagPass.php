<?php

namespace Prokl\ServiceProvider\CompilePasses\TagPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class AbstractTagPass
 * @package Prokl\ServiceProvider\CompilePasses
 *
 * @since 07.11.2020
 * @since 08.11.2020 Сортировка по приоритету.
 */
abstract class AbstractTagPass
{
    /**
     * @var ContainerBuilder $container Контейнер.
     */
    protected $container;

    /**
     * Добавить сервис через метод.
     *
     * @param string $destinationServiceId ID сервиса назначения.
     * @param string $method               Метод.
     * @param string $taggedServiceId      Тэгированный сервис.
     *
     * @return void
     */
    protected function addCall(
        string $destinationServiceId,
        string $method,
        string $taggedServiceId
    ) : void {
        if (!$this->container->has($destinationServiceId)) {
            return;
        }

        $definition = $this->container->findDefinition($destinationServiceId);
        $methodCalls = $definition->getMethodCalls();

        array_splice(
            $methodCalls,
            (int)array_search(
                'configure',
                array_map(
                    /**
                     * @return mixed
                     */
                    function (array $call) {
                        return $call[0];
                    },
                    $methodCalls
                )
            ),
            0,
            [[$method, [new Reference($taggedServiceId)]]]
        );

        $definition->setMethodCalls($methodCalls);
    }

    /**
     * Отсортировать тэги по приоритету (если задан).
     *
     * @param array $data Данные.
     *
     * @return array
     *
     * @since 08.11.2020
     */
    protected function sortByPriority(array $data) : array
    {
        if (count($data) === 0) {
            return [];
        }

        // Расставить приоритеты по-умолчанию (0).
        foreach ($data as $key => $item) {
            if (empty($item[0]['priority'])) {
                $data[$key][0]['priority'] = 0;
            }
        }

        // Отсортировать по приоритету.
        uasort($data, static function (array $a, array $b) {
            return $a[0]['priority'] > $b[0]['priority'];
        });

        // Массив загоняется в метод по принципу первый вошел - последний станешь.
        return array_reverse($data);
    }
}
