<?php

namespace Prokl\ServiceProvider\Utils;

use Closure;

/**
 * Class FactoryClosure
 * @package Prokl\FrameworkExtensionBundle\Services\Utils
 *
 * @since 13.07.2021
 */
class FactoryClosure
{
    /**
     * @param Closure $closure Closure.
     *
     * @return mixed
     */
    public function from(Closure $closure)
    {
        return $closure();
    }
}