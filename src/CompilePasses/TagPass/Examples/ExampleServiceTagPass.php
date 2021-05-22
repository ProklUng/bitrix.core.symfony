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
    /**
     * @var array $services
     */
    public $services;

    /**
     * @param mixed $service
     *
     * @return void
     */
    public function addParams($service) : void
    {
        $this->services[] = $service;
    }
}