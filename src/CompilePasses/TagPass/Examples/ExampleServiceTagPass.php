<?php

namespace Prokl\ServiceProvider\CompilePasses\TagPass\Examples;

/**
 * Class ExampleServiceTagPass
 * @package Prokl\ServiceProvider\CompilePasses\Examples
 *
 * @since 07.11.2020
 */
class ExampleServiceTagPass
{
    public $services;

    /**
     * @param $service
     */
    public function addParams($service)
    {
        $this->services[] = $service;
    }
}