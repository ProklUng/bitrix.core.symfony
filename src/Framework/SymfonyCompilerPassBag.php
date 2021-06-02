<?php

namespace Prokl\ServiceProvider\Framework;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\DependencyInjection\ControllerArgumentValueResolverPass;
use Symfony\Component\HttpKernel\DependencyInjection\RegisterControllerArgumentLocatorsPass;
use Symfony\Component\HttpKernel\DependencyInjection\RemoveEmptyControllerArgumentLocatorsPass;
use Symfony\Component\PropertyInfo\DependencyInjection\PropertyInfoPass;
use Symfony\Component\Routing\DependencyInjection\RoutingResolverPass;
use Symfony\Component\Serializer\DependencyInjection\SerializerPass;
use Symfony\Component\Validator\DependencyInjection\AddConstraintValidatorsPass;

/**
 * Class SymfonyCompilerPassBag
 * @package Prokl\ServiceProvider\Framework
 *
 * @since 04.04.2021
 */
class SymfonyCompilerPassBag extends AbstractSymfonyCompilerPassBag
{
    /**
     * @var array $standartCompilerPasses Пассы Symfony.
     */
    protected $standartCompilerPasses = [
        [
            'pass' => RoutingResolverPass::class,
        ],
        [
            'pass' => SerializerPass::class,
        ],
        [
            'pass' => PropertyInfoPass::class,
        ],
        [
            'pass' => AddConstraintValidatorsPass::class,
        ],
    ];
}
