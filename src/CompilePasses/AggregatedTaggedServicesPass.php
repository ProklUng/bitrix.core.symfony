<?php

namespace Prokl\ServiceProvider\CompilePasses;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class AggregatedTaggedServicesPass.
 * Compile pass для обработки сервисов с тэгом service.bootstrap.
 * @package Prokl\ServiceProvider\CompilePasses
 *
 * @since 21.09.2020 ID сервисов пробрасываются в параметры, чтобы их можно было
 * запускать в случае компилированного контейнера.
 * @since 06.11.2020 Добавление к уже существующим параметрам, а не перезаписывание. Позволяет бандлам
 * подмешивать свои добавления.
 */
final class AggregatedTaggedServicesPass implements CompilerPassInterface
{
    /**
     * @const string TAG_BOOTSTRAP_SERVICES Тэг сервисов запускающихся при загрузке.
     */
    private const TAG_BOOTSTRAP_SERVICES = 'service.bootstrap';

    /**
     * @const string VARIABLE_CONTAINER Название переменной в контейнере.
     */
    private const VARIABLE_CONTAINER = '_bootstrap';

    /**
     * Движуха.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     * @throws Exception Ошибки контейнера.
     */
    public function process(ContainerBuilder $container) : void
    {
        $taggedServices = $container->findTaggedServiceIds(self::TAG_BOOTSTRAP_SERVICES);

        if (count($taggedServices) === 0) {
            return;
        }

        $params = $container->hasParameter(self::VARIABLE_CONTAINER) ?
            (array)$container->getParameter(self::VARIABLE_CONTAINER)
            : [];

        // Сервисы автозапуска.
        $container->setParameter(
            self::VARIABLE_CONTAINER,
            array_merge($params, $taggedServices)
        );
    }
}
