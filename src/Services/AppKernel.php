<?php

namespace Prokl\ServiceProvider\Services;

use Bitrix\Main\Application;
use Prokl\ServiceProvider\Bundles\BundlesLoader;
use LogicException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class AppKernel
 * @package Prokl\ServiceProvider\Services
 *
 * @since 08.10.2020 kernel.site.host
 * @since 22.10.2020 kernel.schema
 * @since 25.10.2020 Наследование от HttpKernel.
 * @since 13.12.2020 Создание директории кэша, если она не существует.
 */
class AppKernel extends Kernel
{
    /**
     * @var string $environment Окружение.
     */
    protected $environment;

    /**
     * @var string $bundlesConfigFile Файл с конфигурацией бандлов.
     */
    protected $bundlesConfigFile = '/local/configs/bundles.php';

    /**
     * @var boolean $debug Отладка? Оно же служит для определения типа окружения.
     */
    protected $debug;

    /**
     * @var ContainerInterface $kernelContainer Копия контейнера.
     */
    protected static $kernelContainer;

    /**
     * @var string $cacheDir Путь к директории с кэшом.
     */
    protected $cacheDir = '/bitrix/cache';

    /**
     * @var string $logDir Путь к директории с логами.
     */
    protected $logDir = '/../../logs';

    /**
     * @var string $projectDir DOCUMENT_ROOT.
     */
    protected $projectDir;

    /**
     * AppKernel constructor.
     *
     * @param string  $environment Окружение.
     * @param boolean $debug       Признак режима отладки.
     */
    public function __construct(string $environment, bool $debug)
    {
        $this->debug = $debug;
        $this->environment = $environment;
        $this->projectDir = $this->getProjectDir();

        parent::__construct($this->environment, $this->debug);

        $this->bundles = $this->registerStandaloneBundles(); // "Standalone" бандлы.
    }

    /**
     * Директория кэша.
     *
     * @return string
     *
     * @since 13.12.2020 Создание директории кэша, если она не существует.
     */
    public function getCacheDir(): string
    {
        $cachePath = $this->getProjectDir() . $this->cacheDir;
        if (!@file_exists($cachePath)) {
            @mkdir($cachePath, 0777, true);
        }

        return $cachePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getProjectDir() . $this->logDir;
    }

    /**
     * Gets the application root dir.
     *
     * @return string The project root dir.
     */
    public function getProjectDir(): string
    {
        if ($this->projectDir === null) {
            $this->projectDir = Application::getDocumentRoot();
        }

        return $this->projectDir;
    }

    /**
     * Параметры ядра. Пути, debug & etc.
     *
     * @return array
     */
    public function getKernelParameters(): array
    {
        $bundlesMetaData = $this->getBundlesMetaData();

        return [
            'kernel.project_dir' => realpath($this->getProjectDir()) ?: $this->getProjectDir(),
            // Deprecated. Для совместимости.
            'kernel.root_dir' => realpath($this->getProjectDir()) ?: $this->getProjectDir(),
            'kernel.environment' => $this->environment,
            'kernel.debug' => $this->debug,
            'kernel.cache_dir' => $this->getCacheDir(),
            'kernel.logs_dir' => $this->getLogDir(),
            'kernel.http.host' => $_SERVER['HTTP_HOST'],
            'kernel.site.host' => $this->getSiteHost(),
            'kernel.schema' => $this->getSchema(),
            'kernel.bundles' => $bundlesMetaData['kernel.bundles'],
            'kernel.bundles_metadata' => $bundlesMetaData['kernel.bundles_metadata'],
            'kernel.container_class' => $this->getContainerClass(),
            'kernel.charset' => $this->getCharset(),
            'kernel.default_locale' => 'ru',
            'debug.container.dump' => $this->debug ? '%kernel.cache_dir%/%kernel.container_class%.xml' : null
        ];
    }

    /**
     * Мета-данные бандлов.
     *
     * @return array[]
     *
     * @since 13.11.2020
     */
    public function getBundlesMetaData() : array
    {
        $bundles = [];
        $bundlesMetadata = [];

        foreach ($this->bundles as $name => $bundle) {
            $bundles[$name] = get_class($bundle);
            $bundlesMetadata[$name] = [
                'path' => $bundle->getPath(),
                'namespace' => $bundle->getNamespace(),
            ];
        }

        return [
            'kernel.bundles' => $bundles,
            'kernel.bundles_metadata' => $bundlesMetadata
        ];
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container Контейнер.
     *
     * @return void
     *
     * @since 12.12.2020
     */
    public function setContainer(?ContainerInterface $container = null) : void
    {
        $this->container = static::$kernelContainer = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        if (static::$kernelContainer === null) {
            throw new LogicException('Cannot retrieve the container from a non-booted kernel.');
        }

        return static::$kernelContainer;
    }

    /**
     * REQUEST_URI.
     *
     * @return string
     *
     * @since 16.10.2020
     */
    public function getRequestUri() : string
    {
        $request = Application::getInstance()->getContext()->getRequest();

        return (string)$request->getRequestUri();
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    /**
     * Регистрация бандла.
     *
     * @return iterable|BundleInterface[]
     *
     * @since 02.06.2021 Если файл не существует - игнорим.
     *
     * @internal пока не используется. Манипуляции с бандлами - через класс BundlesLoader.
     */
    public function registerBundles(): iterable
    {
        $bundleConfigPath = $this->getProjectDir() . $this->bundlesConfigFile;

        if (!@file_exists($bundleConfigPath)) {
            return [];
        }

        /* @noinspection PhpIncludeInspection */
        $contents = require $bundleConfigPath;

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * Регистрация одного бандла.
     *
     * @param object $bundle Бандл.
     *
     * @return void
     * @throws LogicException Когда проскакивают дубликаты бандлов.
     */
    public function registerBundle($bundle) : void
    {
        $name = $bundle->getName();
        if (isset($this->bundles[$name])) {
            throw new LogicException(sprintf('Trying to register two bundles with the same name "%s"', $name));
        }

        $this->bundles[$name] = $bundle;
    }

    /**
     * Регистрация "отдельностоящих" бандлов.
     *
     * @return array
     *
     * @since 25.10.2020
     */
    public function registerStandaloneBundles(): array
    {
        $bundles = BundlesLoader::getBundlesMap();

        foreach ($bundles as $bundle) {
            $this->registerBundle($bundle);
        }

        return $bundles;
    }

    /**
     * Хост сайта.
     *
     * @return string
     *
     * @since 08.10.2020
     */
    private function getSiteHost() : string
    {
        return $this->getSchema() . $_SERVER['HTTP_HOST'];
    }

    /**
     * Schema http or https.
     *
     * @return string
     *
     * @since 22.10.2020
     */
    private function getSchema() : string
    {
        return (!empty($_SERVER['HTTPS'])
            && ($_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443)
        ) ? 'https://' : 'http://';
    }
}
