<?php

namespace Prokl\ServiceProvider\Framework;

/**
 * Interface SymfonyCompilerPassBagInterface
 * @package Prokl\ServiceProvider\Framework
 *
 * @since 05.04.2021
 */
interface SymfonyCompilerPassBagInterface
{
    /**
     * Стандартные compiler pass Symfony.
     *
     * @param array $standartCompilerPasses Compiler passes.
     *
     * @return void
     */
    public function setStandartCompilerPasses(array $standartCompilerPasses): void;

    /**
     * Стандартные compiler pass Symfony.
     *
     * @return array
     */
    public function getStandartCompilerPasses(): array;
}
