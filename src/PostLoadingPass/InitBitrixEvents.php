<?php

namespace Prokl\ServiceProvider\PostLoadingPass;

use Exception;
use InvalidArgumentException;
use Prokl\ServiceProvider\Interfaces\PostLoadingPassInterface;
use Prokl\ServiceProvider\PostLoadingPass\Exceptions\RuntimePostLoadingPassException;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class InitBitrixEvents
 *
 * Инициализация событий Битрикс.
 *
 * @package Prokl\ServiceProvider\PostLoadingPass
 *
 * @since 28.09.2020.
 *
 * @example В Yaml файле:
 *
 * tags:
 *   - { name: bitrix.events.init, module: iblock, event: OnBeforeIBlockElementUpdate, method: addEventOnBeforeIBlockElementUpdate, sort: 10 }
 */
final class InitBitrixEvents implements PostLoadingPassInterface
{
    /**
     * @const string METHOD_INIT_EVENT Метод, инициализирующий события.
     */
    private const METHOD_INIT_EVENT = 'addEvent';

    /**
     * @const string VARIABLE_PARAM_BAG Переменная в ParameterBag.
     */
    private const VARIABLE_PARAM_BAG = '_events';

    /**
     * @inheritDoc
     *
     * @throws RuntimePostLoadingPassException Когда что-то не так с параметрами PostLoadingPasss.
     * @throws Exception
     */
    public function action(Container $containerBuilder): bool
    {
        $result = false;

        try {
            $eventsServices = (array)$containerBuilder->getParameter(self::VARIABLE_PARAM_BAG);
        } catch (InvalidArgumentException $e) {
            return $result;
        }

        if (count($eventsServices) === 0) {
            return $result;
        }

        foreach ($eventsServices as $service => $value) {
            $serviceInstance = $containerBuilder->get($service);
            if (is_array($value) && !empty($value) && $serviceInstance) {
                $this->processEventItem($serviceInstance, $value);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Обработать параметры события и запустить обработчик.
     *
     * @param object $service Экземпляр сервиса.
     * @param array  $arData  Данные.
     *
     * @return boolean
     * @throws RuntimePostLoadingPassException Когда что-то не так с параметрами PostLoadingPasss.
     */
    private function processEventItem($service, array $arData): bool
    {
        $result = false;

        foreach ($arData as $item) {
            if (empty($item)) {
                throw new RuntimePostLoadingPassException(
                    'InitEvents PostLoadingPass: params void.'
                );
            }

            if (empty($item['event'])) {
                throw new RuntimePostLoadingPassException(
                    'InitEvents PostLoadingPass: name event apsent.'
                );
            }

            $module = $item['module'] ?? ''; // Модуль.
            $priority = $item['sort'] ?? 10; // Приоритет.
            $method = $item['method'] ?? self::METHOD_INIT_EVENT; // Метод.

            if (!method_exists($service, $method)) {
                throw new RuntimePostLoadingPassException(
                    sprintf(
                        'InitEvents PostLoadingPass: method %s of class listener %s not exist.',
                        $method,
                        get_class($service)
                    )
                );
            }

            // Инициализация события.
            AddEventHandler($module, $item['event'], [$service, $method], $priority);

            $result = true;
        }

        return $result;
    }
}
