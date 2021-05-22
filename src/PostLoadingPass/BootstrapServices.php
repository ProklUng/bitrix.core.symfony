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
 * @since 28.09.2020
 */
final class BootstrapServices implements PostLoadingPassInterface
{
    /**
     * @const string VARIABLE_PARAM_BAG Переменная в ParameterBag.
     */
    private const VARIABLE_PARAM_BAG = '_bootstrap';

    /**
     * @inheritDoc
     * @throws Exception
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

        foreach ($bootstrapServices as $service => $value) {
            $containerBuilder->get($service);
        }

        return true;
    }
}
