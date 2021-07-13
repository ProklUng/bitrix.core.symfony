<?php

namespace Prokl\ServiceProvider\Utils\Loaders;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\ModuleManager;
use Prokl\ServiceProvider\Utils\BitrixSettingsDiAdapter;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Loader\ProtectedPhpFileLoader;

/**
 * Class PhpLoaderSettingsBitrix
 * Загрузчик битриксовых конфигурационных файлов
 * @package Prokl\ServiceProvider\Utils\Loaders
 *
 * @since 12.07.2021
 */
class PhpLoaderSettingsBitrix extends FileLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, string $type = null)
    {
        // the container and loader variables are exposed to the included file below
        $container = $this->container;

        $path = $this->locator->locate($resource);

        $this->setCurrentDir(\dirname($path));
        $this->container->fileExists($path);

        // the closure forbids access to the private scope in the included file
        $load = \Closure::bind(function ($path, $env) {
            return $this->loadBitrixConfig('services', true);
        }, $this, ProtectedPhpFileLoader::class);

        try {
            $callback = $load($path, $this->env);
            if (is_array($callback)) {
                $adapter = new BitrixSettingsDiAdapter();
                $adapter->importServices($container, $callback);
            }
        } finally {
            $this->instanceof = [];
            $this->registerAliasesForSinglyImplementedInterfaces();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, string $type = null)
    {
        if (!\is_string($resource)
            // ToDo более строгая проверка на нужный файл.
            || stripos($resource, '.settings') === false
        ) {
            return false;
        }

        if (null === $type && 'php' === pathinfo($resource, \PATHINFO_EXTENSION)) {
            return true;
        }

        return 'php' === $type;
    }

    /**
     * Загрузка битриксовых конфигов.
     *
     * @param string  $key                 Ключ в параметрах битриксовых файлов.
     * @param boolean $loadModulesServices Загружать такую же секцию в установленных модулях.
     *
     * @return array
     */
    public function loadBitrixConfig(string $key, bool $loadModulesServices = true) : array
    {
        $mainBitrixServices = Configuration::getInstance()->get($key) ?? [];

        // Собрать конфиги всех установленных модулей.
        $servicesModules = [];

        if ($loadModulesServices) {
            foreach (ModuleManager::getInstalledModules() as $module) {
                $services = Configuration::getInstance($module['ID'])->get($key) ?? [];
                if (count($services) > 0) {
                    $servicesModules[] = $services;
                }
            }
        }

        return array_merge($mainBitrixServices, ...$servicesModules);
    }
}
