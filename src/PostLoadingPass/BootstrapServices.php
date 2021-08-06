<?php

namespace Prokl\ServiceProvider\PostLoadingPass;

use Exception;
use Prokl\ServiceProvider\Interfaces\PostLoadingPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Class BootstrapServices
 *
 * Автозагрузка сервисов.
 *
 * @package Prokl\ServiceProvider\PostLoadingPass
 *
 * @since 26.09.2020
 * @since 27.09.2020 Доработки.
 * @since 04.05.2021 Исключения сервисов автозагрузки больше не глушатся.
 * @since 06.08.2021 Сортировка по приоритету.
 */
final class BootstrapServices implements PostLoadingPassInterface
{
    /**
     * @const string VARIABLE_PARAM_BAG Переменная в ParameterBag.
     */
    private const VARIABLE_PARAM_BAG = '_bootstrap';

    /**
     * @inheritDoc
     * @throws Exception Когда проблемы с получением сервиса из контейнера.
     */
    public function action(Container $containerBuilder) : bool
    {
        try {
            $bootstrapServices = (array)$containerBuilder->getParameter(self::VARIABLE_PARAM_BAG);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        if (count($bootstrapServices) === 0) {
            return false;
        }

        $result = [];
        foreach ($bootstrapServices as $service => $value) {
            $priority = 0;
            if (array_key_exists(0, $value) && is_array($value[0])) {
                if (array_key_exists('priority', $value[0])) {
                    $priority = (int)$value[0]['priority'];
                }
            }

            $result[] = ['service' => $service, 'priority' => $priority];
        }

        usort($result, static function ($a, $b) : bool {
            // @phpstan-ignore-line
            return $a['priority'] > $b['priority'];
        });

        foreach ($result as $service) {
            $containerBuilder->get($service['service']);
        }

        return true;
    }
}