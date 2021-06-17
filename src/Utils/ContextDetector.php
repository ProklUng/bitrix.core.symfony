<?php

namespace Prokl\ServiceProvider\Utils;

/**
 * Class ContextDetector
 * @package Prokl\ServiceProvider\Utils
 *
 * @since 17.06.2021
 */
class ContextDetector
{
    /**
     * Проверка - запускается консольная команда.
     *
     * @return boolean
     */
    public static function isCli() : bool
    {
        if (PHP_BINARY && in_array(PHP_SAPI, ['cli', 'cli-server', 'phpdbg']) && is_file(PHP_BINARY)) {
            return true;
        }

        return false;
    }
}