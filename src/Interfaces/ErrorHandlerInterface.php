<?php

namespace Prokl\ServiceProvider\Interfaces;

/**
 * Interface ErrorHandlerInterface
 * @package Prokl\ServiceProvider\Interfaces
 *
 * @since 31.07.2021
 */
interface ErrorHandlerInterface
{
    /**
     * Показать экран "The site is experiencing technical difficulties",
     * но со своим текстом.
     *
     * @param string $errorMessage Текст сообщения.
     *
     * @return mixed
     */
    public function die(string $errorMessage = '');
}