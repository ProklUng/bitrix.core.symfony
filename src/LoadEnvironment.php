<?php

namespace Prokl\ServiceProvider;

use Symfony\Component\Dotenv\Dotenv;

/**
 * Class LoadEnvironment
 * Хэлпер: загрузка окружения (dev или prod).
 * @package Prokl\ServiceProvider
 */
class LoadEnvironment
{
    /**
     * @var string $pathEnvFile Путь к env файлам.
     */
    private $pathEnvFile;

    /**
     * LoadEnvironment constructor.
     *
     * @param string $pathEnvFile Путь к env файлам.
     */
    public function __construct(string $pathEnvFile)
    {
        $this->pathEnvFile = $pathEnvFile;
    }

    /**
     * Загрузка конфигурации окружения.
     *
     * @return void
     */
    public function load() : void
    {
        /** Путь к конфигурации окружения. .env.prod - продакшен. */
        $pathEnvFile = @file_exists($this->pathEnvFile . '/.env.prod')
            ? $this->pathEnvFile . '/.env.prod'
            :
            $_SERVER['DOCUMENT_ROOT'] . '/.env';

        if (@file_exists($this->pathEnvFile . '/.env.local')) {
            $pathEnvFile = $this->pathEnvFile . '/.env.local';
        }

        $dotenv = new Dotenv();

        $dotenv->loadEnv($pathEnvFile);
    }

    /**
     * Обработка переменных окружения.
     *
     * @return void
     */
    public function process() : void
    {
        $_ENV['DEBUG'] = $_ENV['DEBUG'] ?? false;

        if (array_key_exists('APP_DEBUG', $_ENV) && $_ENV['APP_DEBUG'] !== null) {
            $_ENV['DEBUG'] = (bool)$_ENV['APP_DEBUG'];
        }

        $_SERVER = array_merge($_SERVER, $_ENV);

        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
        $_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int)$_SERVER['APP_DEBUG']
        || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }
}
