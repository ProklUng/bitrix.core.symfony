<?php

namespace Prokl\ServiceProvider\Tests\Fixtures;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class TestingBundle
 * @package Prokl\ServiceProvider\Tests\Fixtures
 */
class TestingBundle extends Bundle
{
    public $booted = false;

    public static $booted_static = false;

    /**
     * @inheritDoc
     */
    public function getContainerExtension()
    {
        return new TestingBundleExtension();
    }

    /**
     * @inheritDoc
     */
    public function boot() : void
    {
        parent::boot();
        $this->booted = true;
        static::$booted_static = true;
    }
}