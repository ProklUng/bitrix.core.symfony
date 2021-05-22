<?php

namespace Prokl\ServiceProvider\PostLoadingPass;

use Exception;
use Prokl\ServiceProvider\Interfaces\PostLoadingPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Twig\Extension\ExtensionInterface;

/**
 * Class TwigExtensionApply
 *
 * Автозагрузка Twig Extensions.
 *
 * @package Prokl\ServiceProvider\PostLoadingPass
 *
 * @since 11.10.2020
 */
final class TwigExtensionApply implements PostLoadingPassInterface
{
    /**
     * @const string VARIABLE_PARAM_BAG Переменная в ParameterBag.
     */
    private const VARIABLE_PARAM_BAG = '_twig_extension';

    /**
     * @inheritDoc
     */
    public function action(Container $containerBuilder) : bool
    {
        $result = false;

        try {
            $twigExtensions = (array)$containerBuilder->getParameter(self::VARIABLE_PARAM_BAG);
        } catch (InvalidArgumentException $e) {
            return $result;
        }

        if (count($twigExtensions) === 0) {
            return $result;
        }

        $twig = $containerBuilder->get('twig.instance');
        if ($twig === null) {
            throw new \RuntimeException('Twig.instance service not found');
        }

        foreach ($twigExtensions as $service => $value) {
            try {
                /**
                 * @var ExtensionInterface $extension
                 */
                $extension = $containerBuilder->get($service);
                $twig->addExtension($extension);

                $result = true;
            } catch (Exception $e) {
                continue;
            }
        }

        return $result;
    }
}
