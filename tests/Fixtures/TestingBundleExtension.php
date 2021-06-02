<?php

namespace Prokl\ServiceProvider\Tests\Fixtures;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class TestingBundleExtension
 * @package Prokl\ServiceProvider\Tests\Fixtures
 */
class TestingBundleExtension extends Extension
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container) : void
    {

    }
}