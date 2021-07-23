<?php

namespace Prokl\ServiceProvider;

use Bitrix\Main\Application;
use CMain;
use Exception;
use InvalidArgumentException;
use Prokl\ServiceProvider\Bundles\BundlesLoader;
use Prokl\ServiceProvider\Framework\AutoconfigureConfig;
use Prokl\ServiceProvider\Framework\SymfonyCompilerPassBag;
use Prokl\ServiceProvider\Services\AppKernel;
use Prokl\ServiceProvider\Utils\ErrorScreen;
use Prokl\ServiceProvider\Utils\Loaders\PhpLoaderSettingsBitrix;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use RuntimeException;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Class ServiceProvider
 * @package Prokl\ServiceProvider
 *
 * @since 11.09.2020 Подключение возможности обработки событий HtppKernel через Yaml конфиг.
 * @since 21.09.2020 Исправление ошибки: сервисы, помеченные к автозагрузке не запускались в
 * случае компилированного контейнера.
 * @since 28.09.2020 Доработка.
 * @since 24.10.2020 Загрузка "автономных" бандлов Symfony.
 * @since 08.11.2020 Устранение ошибки, связанной с многократной загрузкой конфигурации бандлов.
 * @since 12.11.2020 Значение debug передаются снаружи. Рефакторинг.
 * @since 14.11.2020 Загрузка конфигураций бандлов.
 * @since 12.12.2020 Полноценный контейнер в kernel.
 * @since 12.12.2020 DoctrineDbalExtension.
 * @since 21.12.2020 Нативная поддержка нативных аннотированных роутов.
 * @since 03.03.2021 Разные компилированные контейнеры в зависмости от файла конфигурации.
 * @since 20.03.2021 Поддержка разных форматов (Yaml, php, xml) конфигурации контейнера. Удаление ExtraFeature
 * внутрь соответствующего класса.
 * @since 04.04.2021 Вынес стандартные compile pass Symfony в отдельный класс.
 * @since 14.04.2021 Метод boot бандлов вызывается теперь после компиляции контейнера.
 * @since 27.04.2021 Баг-фикс: при скомпилированном контейнере не запускался метод boot бандлов.
 * @since 26.06.2021 Автоконфигурация тэгов вынесена в отдельный метод.
 *
 * @psalm-consistent-constructor
 */
class ServiceProvider
{
    /**
     * @const string SERVICE_CONFIG_FILE Конфигурация сервисов.
     */
    private const SERVICE_CONFIG_FILE = 'local/configs/services.yaml';

    /**
     * @const string COMPILED_CONTAINER_PATH Файл с сскомпилированным контейнером.
     */
    private const COMPILED_CONTAINER_FILE = '/container.php';

    /**
     * @const string CONFIG_EXTS Расширения конфигурационных файлов.
     */
    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @var ContainerBuilder $containerBuilder Контейнер.
     */
    protected static $containerBuilder;

    /**
     * @var string $pathBundlesConfig Путь к конфигурации бандлов.
     */
    protected $pathBundlesConfig = '/local/configs/standalone_bundles.php';

    /**
     * @var string $configDir Папка, где лежат конфиги.
     */
    protected $configDir = '/local/configs';

    /**
     * @var string $kernelServiceClass Класс, реализующий сервис kernel.
     */
    protected $kernelServiceClass = AppKernel::class;

    /**
     * @var array $standartCompilerPasses Пассы Symfony.
     */
    protected $standartCompilerPasses = [];

    /**
     * @var string $symfonyCompilerClass Класс с симфоническими compiler passes.
     */
    protected $symfonyCompilerClass = SymfonyCompilerPassBag::class;

    /**
     * @var string $cacheDir Путь к кэшу.
     */
    protected $cacheDir = '/bitrix/cache/';

    /**
     * @var string $projectRoot DOCUMENT_ROOT.
     */
    protected $projectRoot = '';

    /**
     * @var array Конфигурация бандлов.
     */
    protected $bundles = [];

    /**
     * @var ErrorScreen $errorHandler Обработчик ошибок.
     */
    protected $errorHandler;

    /**
     * @var Filesystem $filesystem Файловая система.
     */
    private $filesystem;

    /**
     * @var BundlesLoader $bundlesLoader Загрузчик бандлов.
     */
    private $bundlesLoader;

    /**
     * @var string $filename Файл конфигурации контейнера.
     */
    private $filename;

    /**
     * @var array $compilerPassesBag Набор Compiler Pass.
     */
    private $compilerPassesBag = [];

    /**
     * @var array $postLoadingPassesBag Пост-обработчики (PostLoadingPass) контейнера.
     */
    private $postLoadingPassesBag = [];

    /**
     * @var string $environment Среда.
     */
    private $environment;

    /**
     * @var boolean $debug Режим dev?
     */
    private $debug;

    /**
     * ServiceProvider constructor.
     *
     * @param string      $filename          Конфиг.
     * @param string|null $pathBundlesConfig Путь к конфигурации бандлов.
     *
     * @throws Exception Ошибка инициализации контейнера.
     * @since 01.06.2021 Путь к конфигурации бандлов можно задать снаружи.
     */
    public function __construct(
        string $filename = self::SERVICE_CONFIG_FILE,
        ?string $pathBundlesConfig = null
    ) {
        $this->setupEnv();

        $this->errorHandler = new ErrorScreen(new CMain());
        $this->filesystem = new Filesystem();

        if (!$filename) {
            $filename = self::SERVICE_CONFIG_FILE;
        }

        if ($pathBundlesConfig !== null) {
            $this->pathBundlesConfig = $pathBundlesConfig;
        }

        $this->filename = $filename;

        if (static::$containerBuilder !== null) {
            return;
        }

        $frameworkCompilePasses = new $this->symfonyCompilerClass;
        $this->standartCompilerPasses = $frameworkCompilePasses->getStandartCompilerPasses();

        // Кастомные Compile pass & PostLoadingPass.
        $customCompilePassesBag = new CustomCompilePassBag();

        $this->compilerPassesBag = $customCompilePassesBag->getCompilerPassesBag();
        $this->postLoadingPassesBag = $customCompilePassesBag->getPostLoadingPassesBag();

        $this->projectRoot = Application::getDocumentRoot();

        $this->boot();
    }

    /**
     * Сервис по ключу.
     *
     * @param string $id ID сервиса.
     *
     * @return mixed
     * @throws Exception Ошибки контейнера.
     */
    public function get(string $id)
    {
        return static::$containerBuilder->get($id);
    }

    /**
     * Контейнер.
     *
     * @return ContainerInterface
     */
    public function container() : ContainerInterface
    {
        return static::$containerBuilder ?: $this->initContainer($this->filename);
    }

    /**
     * Жестко установить контейнер.
     *
     * @param PsrContainerInterface $container Контейнер.
     *
     * @return void
     */
    public function setContainer(PsrContainerInterface $container): void
    {
        static::$containerBuilder  = $container;
    }

    /**
     * Reboot.
     *
     * @return void
     * @throws Exception
     */
    public function reboot() : void
    {
        $this->shutdown();
        $this->boot();
    }

    /**
     * Shutdown.
     *
     * @return void
     * @throws Exception
     */
    public function shutdown() : void
    {
        if (static::$containerBuilder === null) {
            return;
        }

        if (static::$containerBuilder->has('kernel')) {
            /** @var AppKernel $kernel */
            $kernel = static::$containerBuilder->get('kernel');
            $kernel->setContainer(null);
        }

        foreach (BundlesLoader::getBundlesMap() as $bundle) {
            $bundle->shutdown();
            $bundle->setContainer(null);
        }

        $this->bundles = [];
        BundlesLoader::clearBundlesMap();

        static::$containerBuilder = null;
    }

    /**
     * Манипуляции с переменными окружения.
     *
     * @return void
     */
    private function setupEnv() : void
    {
        $_ENV['DEBUG'] = $_ENV['DEBUG'] ?? false;
        if ($_ENV['DEBUG'] !== false) {
            if (is_string($_ENV['DEBUG'])) {
                $_ENV['DEBUG'] = $_ENV['DEBUG'] === 'true' || $_ENV['DEBUG'] === '1';
            } else {
                $_ENV['DEBUG'] = (bool)$_ENV['DEBUG'];
            }
        }

        $this->environment = $_ENV['DEBUG'] ? 'dev' : 'prod';

        if (array_key_exists('APP_ENV', $_ENV) && $_ENV['APP_ENV'] !== null) {
            $this->environment = $_ENV['APP_ENV'];
        }

        $this->debug = $_ENV['DEBUG'];
    }

    /**
     * Boot.
     *
     * @throws Exception
     */
    private function boot() : void
    {
        $result = $this->initContainer($this->filename);
        if (!$result) {
            $this->errorHandler->die('Container DI inititalization error.');
            throw new Exception('Container DI inititalization error.');
        }
    }

    /**
     * Инициализировать контейнер.
     *
     * @param string $fileName Конфиг.
     *
     * @return mixed
     *
     * @since 28.09.2020 Доработка.
     */
    private function initContainer(string $fileName)
    {
        // Если в dev режиме, то не компилировать контейнер.
        if ((bool)$_ENV['DEBUG'] === true) {
            if (static::$containerBuilder !== null) {
                return static::$containerBuilder;
            }

            // Загрузить, инициализировать и скомпилировать контейнер.
            static::$containerBuilder = $this->initialize($fileName);

            // Исполнить PostLoadingPasses.
            $this->runPostLoadingPasses();

            return static::$containerBuilder;
        }

        // Создать директорию
        // для компилированного контейнера.
        $this->createCacheDirectory();

        /** Путь к скомпилированному контейнеру. */
        $compiledContainerFile = $this->getPathCacheDirectory($this->filename) . self::COMPILED_CONTAINER_FILE;

        $containerConfigCache = new ConfigCache($compiledContainerFile, true);
        // Класс скомпилированного контейнера.
        $classCompiledContainerName = $this->getContainerClass() . md5($this->filename);

        if (!$containerConfigCache->isFresh()) {
            // Загрузить, инициализировать и скомпилировать контейнер.
            static::$containerBuilder = $this->initialize($fileName);

            // Блокировка на предмет конкурентных запросов.
            $lockFile = $this->getPathCacheDirectory($this->filename) . '/container.lock';

            // Silence E_WARNING to ignore "include" failures - don't use "@" to prevent silencing fatal errors
            $errorLevel = error_reporting(\E_ALL ^ \E_WARNING);

            $lock = false;
            try {
                if ($lock = fopen($lockFile, 'w')) {
                    flock($lock, \LOCK_EX | \LOCK_NB, $wouldBlock);
                    if (!flock($lock, $wouldBlock ? \LOCK_SH : \LOCK_EX)) {
                        fclose($lock);
                        @unlink($lockFile);
                        $lock = null;
                    }
                } else {
                    // Если в файл контейнера уже что-то пишется, то вернем свежую копию контейнера.
                    flock($lock, \LOCK_UN);
                    fclose($lock);
                    @unlink($lockFile);

                    // Исполнить PostLoadingPasses.
                    $this->runPostLoadingPasses();

                    return static::$containerBuilder;
                }
            } catch (Throwable $e) {
            } finally {
                error_reporting($errorLevel);
            }

            $this->dumpContainer($containerConfigCache, static::$containerBuilder, $classCompiledContainerName);

            if ($lock) {
                flock($lock, \LOCK_UN);
                fclose($lock);
                @unlink($lockFile);
            }
        }

        // Подключение скомпилированного контейнера.
        /** @noinspection PhpIncludeInspection */
        require_once $compiledContainerFile;

        $classCompiledContainerName = '\\'.$classCompiledContainerName;

        static::$containerBuilder = new $classCompiledContainerName();

        // Boot bundles.
        BundlesLoader::bootAfterCompilingContainer(static::$containerBuilder);

        // Исполнить PostLoadingPasses.
        $this->runPostLoadingPasses();

        return static::$containerBuilder;
    }

    /**
     * Dumps the service container to PHP code in the cache.
     *
     * @param ConfigCache      $cache     Кэш.
     * @param ContainerBuilder $container Контейнер.
     * @param string           $class     The name of the class to generate.
     *
     * @return void
     *
     * @since 20.03.2021 Форк оригинального метода с приближением к реальности.
     */
    private function dumpContainer(ConfigCache $cache, ContainerBuilder $container, string $class) : void
    {
        // Опция в конфиге - компилировать ли контейнер.
        if ($container->hasParameter('compile.container')
            &&
            !$container->getParameter('compile.container')) {
            return;
        }

        // Опция - дампить как файлы. По умолчанию - нет.
        $asFiles = false;
        if ($container->hasParameter('container.dumper.inline_factories')) {
            $asFiles = $container->getParameter('container.dumper.inline_factories');
        }

        $dumper = new PhpDumper(static::$containerBuilder);
        if (class_exists(\ProxyManager\Configuration::class) && class_exists(ProxyDumper::class)) {
            $dumper->setProxyDumper(new ProxyDumper());
        }

        $content = $dumper->dump(
            [
                'class' => $class,
                'file' => $cache->getPath(),
                'as_files' => $asFiles,
                'debug' => $this->debug,
                'build_time' => static::$containerBuilder->hasParameter('kernel.container_build_time')
                    ? static::$containerBuilder->getParameter('kernel.container_build_time') : time(),
                'preload_classes' => array_map('get_class', $this->bundles),
            ]
        );

        // Если as_files = true.
        if (is_array($content)) {
            $rootCode = array_pop($content);
            $dir = \dirname($cache->getPath()).'/';

            foreach ($content as $file => $code) {
                $this->filesystem->dumpFile($dir.$file, $code);
                @chmod($dir.$file, 0666 & ~umask());
            }

            $legacyFile = \dirname($dir.key($content)).'.legacy';
            if (is_file($legacyFile)) {
                @unlink($legacyFile);
            }

            $content = $rootCode;
        }

        $cache->write(
            $content, // @phpstan-ignore-line
            static::$containerBuilder->getResources()
        );
    }

    /**
     * Gets the container class.
     *
     * @return string The container class.
     * @throws InvalidArgumentException If the generated classname is invalid.
     */
    private function getContainerClass() : string
    {
        $class = static::class;
        $class = false !== strpos($class, "@anonymous\0") ? get_parent_class($class).str_replace('.', '_', ContainerBuilder::hash($class))
                                                                : $class;
        $class = str_replace('\\', '_', $class).ucfirst($this->environment).($this->debug ? 'Debug' : '').'Container';

        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
            throw new InvalidArgumentException(
                sprintf('The environment "%s" contains invalid characters, it can only contain characters allowed in PHP class names.', $this->environment)
            );
        }

        return $class;
    }

    /**
     * Загрузить контейнер.
     *
     * @param string $fileName Конфиг.
     *
     * @return boolean|ContainerBuilder
     *
     * @throws Exception Ошибки контейнера.
     *
     * @since 28.09.2020 Набор стандартных Compile Pass. Кастомные Compiler Pass.
     * @since 11.09.2020 Подключение возможности обработки событий HtppKernel через Yaml конфиг.
     */
    private function loadContainer(string $fileName)
    {
        static::$containerBuilder = new ContainerBuilder();
        // Если изменился этот файл, то перестроить контейнер.
        static::$containerBuilder->addObjectResource($this);

        $this->setDefaultParamsContainer();

        // Инициализация автономных бандлов.
        $this->loadSymfonyBundles();

        // Набор стандартных Compile Pass
        $passes = new PassConfig();
        $allPasses = $passes->getPasses();

        foreach ($allPasses as $pass) {
            // Тонкость: MergeExtensionConfigurationPass добавляется в BundlesLoader.
            // Если не проигнорировать здесь, то он вызовется еще раз.
            if (get_class($pass) === MergeExtensionConfigurationPass::class) {
                continue;
            }
            static::$containerBuilder->addCompilerPass($pass);
        }

        $this->registerAutoconfig();
        $this->standartSymfonyPasses();

        // Локальные compile pass.
        foreach ($this->compilerPassesBag as $compilerPass) {
            /** @var CompilerPassInterface $passInitiated */
            $passInitiated = !empty($compilerPass['params']) ? new $compilerPass['pass'](...$compilerPass['params'])
                :
                new $compilerPass['pass'];

            // Фаза. По умолчанию PassConfig::TYPE_BEFORE_OPTIMIZATION
            $phase = !empty($compilerPass['phase']) ? $compilerPass['phase'] : PassConfig::TYPE_BEFORE_OPTIMIZATION;

            static::$containerBuilder->addCompilerPass($passInitiated, $phase);
        }

        // Подключение возможности обработки событий HttpKernel через Yaml конфиг.
        // tags:
        //      - { name: kernel.event_listener, event: kernel.request, method: handle }
        static::$containerBuilder->register('event_dispatcher', EventDispatcher::class);

        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->setHotPathEvents([
            KernelEvents::REQUEST,
            KernelEvents::CONTROLLER,
            KernelEvents::CONTROLLER_ARGUMENTS,
            KernelEvents::RESPONSE,
            KernelEvents::FINISH_REQUEST,
        ]);

        static::$containerBuilder->addCompilerPass($registerListenersPass);

        try {
            // Загрузка основного конфига контейнера.
            if (!$this->loadContainerConfig($fileName, static::$containerBuilder)) {
                return false;
            }

            // Подгрузить конфигурации из папки config.
            $this->configureContainer(
                static::$containerBuilder,
                $this->getContainerLoader(static::$containerBuilder)
            );

            // Контейнер в AppKernel, чтобы соответствовать Symfony.
            if (static::$containerBuilder->has('kernel')) {
                $kernelService = static::$containerBuilder->get('kernel');
                if ($kernelService) {
                    $kernelService->setContainer(static::$containerBuilder);
                }
            }

            return static::$containerBuilder;
        } catch (Exception $e) {
            $this->errorHandler->die('Ошибка загрузки Symfony Container: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Загрузить, инициализировать и скомпилировать контейнер.
     *
     * @param string $fileName Конфигурационный файл.
     *
     * @return null|ContainerBuilder
     *
     * @since 28.09.2020
     */
    private function initialize(string $fileName): ?ContainerBuilder
    {
        try {
            $this->loadContainer($fileName);

            // Дополнить переменные приложения сведениями о зарегистрированных бандлах.
            static::$containerBuilder->get('kernel')->registerStandaloneBundles();

            /** @var array $kernelParams */
            $kernelParams = static::$containerBuilder->get('kernel')->getKernelParameters();

            static::$containerBuilder->getParameterBag()->add($kernelParams);

            $this->bundlesLoader->registerExtensions(static::$containerBuilder);

            static::$containerBuilder->compile(true);

            // Boot bundles.
            $this->bundlesLoader->boot(static::$containerBuilder);
        } catch (Exception $e) {
            $this->errorHandler->die(
                $e->getMessage().'<br><br><pre>'.$e->getTraceAsString().'</pre>'
            );

            return null;
        }

        return static::$containerBuilder;
    }

    /**
     * Параметры контейнера и регистрация сервиса kernel.
     *
     * @return void
     *
     * @throws Exception Ошибки контейнера.
     *
     * @since 12.11.2020 Полная переработка. Регистрация сервиса.
     */
    private function setDefaultParamsContainer() : void
    {
        if (!static::$containerBuilder->hasDefinition('kernel')) {
            $this->registerKernel($this->kernelServiceClass);
        }

        /** @var array $kernelParams */
        $kernelParams = static::$containerBuilder->get('kernel')->getKernelParameters();

        static::$containerBuilder->getParameterBag()->add($kernelParams);
    }

    /**
     * Регистрация kernel сервиса.
     *
     * @param string $kernelClass Класс Kernel.
     *
     * @return void
     *
     * @since 11.07.2021
     */
    private function registerKernel(string $kernelClass) : void
    {
        static::$containerBuilder->register('kernel', $kernelClass)
            ->addTag('service.bootstrap')
            ->setAutoconfigured(true)
            ->setPublic(true)
            ->setArguments([$this->environment, $this->debug]);
    }

    /**
     * Если надо создать директорию для компилированного контейнера.
     *
     * @return void
     */
    private function createCacheDirectory() : void
    {
        $dir = $this->getPathCacheDirectory($this->filename);

        if (!$this->filesystem->exists($dir)) {
            try {
                $this->filesystem->mkdir($dir);
            } catch (IOExceptionInterface $exception) {
                $this->errorHandler->die('An error occurred while creating your directory at '. (string)$exception->getPath());
            }
        }
    }

    /**
     * Путь к директории с компилированным контейнером.
     *
     * @param string $filename Конфигурация.
     *
     * @return string
     *
     * @since 03.03.2021
     * @since 28.06.2021 Путь к кэшу в зависимости от SITE_ID.
     */
    protected function getPathCacheDirectory(string $filename) : string
    {
        $siteId = 's1';
        if (defined(SITE_ID)) {
            $siteId = SITE_ID;
        }

        return $this->projectRoot . $this->cacheDir . '/'  .
               $siteId . '/containers/'. md5($filename);
    }

    /**
     * Compiler passes.
     *
     * @return void
     *
     * @since 28.09.2020
     *
     * @see FrameworkBundle
     */
    private function standartSymfonyPasses(): void
    {
        // Применяем compiler passes.
        foreach ($this->standartCompilerPasses as $pass) {
            if (!array_key_exists('pass', $pass) || !class_exists($pass['pass'])) {
                continue;
            }
            static::$containerBuilder->addCompilerPass(
                new $pass['pass'],
                $pass['phase'] ?? PassConfig::TYPE_BEFORE_OPTIMIZATION
            );
        }
    }

    /**
     * Регистрация автоконфигурируемых тэгов.
     *
     * @return void
     * @throws RuntimeException Когда необходимая зависимость не существует.
     */
    private function registerAutoconfig() : void
    {
        $autoConfigure = new AutoconfigureConfig();

        foreach ($autoConfigure->getAutoConfigure() as $tag => $class) {
            static::$containerBuilder->registerForAutoconfiguration($class)
                                     ->addTag($tag);
        }
    }

    /**
     * Загрузка "автономных" бандлов Symfony.
     *
     * @return void
     *
     * @throws InvalidArgumentException  Не найден класс бандла.
     *
     * @since 24.10.2020
     */
    private function loadSymfonyBundles() : void
    {
        $this->bundlesLoader = new BundlesLoader(
            static::$containerBuilder,
            $this->environment,
            $this->pathBundlesConfig
        );

        $this->bundlesLoader->load(); // Загрузить бандлы.

        $this->bundles = $this->bundlesLoader->bundles();
    }

    /**
     * Запустить PostLoadingPasses.
     *
     * @return void
     *
     * @since 26.09.2020
     * @since 21.03.2021 Маркер, что пасс уже запускался.
     */
    private function runPostLoadingPasses(): void
    {
        /**
         * Отсортировать по приоритету.
         *
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress InvalidScalarArgument
         */
        usort($this->postLoadingPassesBag, static function ($a, $b) : bool {
            // @phpstan-ignore-line
            return $a['priority'] > $b['priority'];
        });

        // Запуск.
        foreach ($this->postLoadingPassesBag as $key => $postLoadingPass) {
            if (class_exists($postLoadingPass['pass']) && !array_key_exists('runned', $postLoadingPass)) {
                $class = new $postLoadingPass['pass'];
                $class->action(static::$containerBuilder);

                // Отметить, что пасс уже запускался.
                $this->postLoadingPassesBag[$key]['runned'] = true;
            }
        }
    }

    /**
     * Загрузка конфигурационного файла контейнера.
     *
     * @param string           $fileName         Конфигурационный файл.
     * @param ContainerBuilder $containerBuilder Контейнер.
     *
     * @return boolean
     * @throws Exception Ошибки контейнера.
     *
     * @since 20.03.2021
     */
    private function loadContainerConfig(string $fileName, ContainerBuilder $containerBuilder) : bool
    {
        $loader = $this->getContainerLoader($containerBuilder);

        try {
            $loader->load($this->projectRoot . '/' . $fileName);
            $loader->load(__DIR__ . '/../config/base.yaml');

            $this->loadBitrixServiceLocatorConfigs($loader);

            return true;
        } catch (Exception $e) {
            $this->errorHandler->die('Сервис-контейнер: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Конфиги битриксового сервис-локатора.
     *
     * @param DelegatingLoader $loader Загрузчик.
     *
     * @return void
     * @throws Exception Когда что-то не так с файлами конфигураций.
     *
     * @since 13.07.2021
     */
    protected function loadBitrixServiceLocatorConfigs(DelegatingLoader $loader) : void
    {
        // Если не найден '/bitrix/.settings.php', то у нас проблемы с Битриксом (не установлен)
        // Выбросит исключение.
        $loader->load($this->projectRoot . '/bitrix/.settings.php');

        if ($this->filesystem->exists($this->projectRoot . '/bitrix/.settings_extra.php')) {
            $loader->load($this->projectRoot . '/bitrix/.settings_extra.php');
        }
    }

    /**
     * Загрузка конфигураций в различных форматах из папки configs.
     *
     * @param ContainerBuilder $container Контейнер.
     * @param LoaderInterface  $loader    Загрузчик.
     *
     * @return void
     * @throws Exception Ошибки контейнера.
     *
     * @since 06.11.2020
     * @throws RuntimeException Когда директория с конфигами не существует.
     */
    private function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = $this->projectRoot . $this->configDir;

        if (!@file_exists($confDir)) {
            throw new RuntimeException('Config directory ' . $confDir . ' not exist.');
        }

        $container->setParameter('container.dumper.inline_class_loader', true);

        if (is_dir($confDir.'/packages')) {
            $loader->load($confDir.'/packages/*'.self::CONFIG_EXTS, 'glob');
        }
       
        if (is_dir($confDir . '/packages/' . $this->environment)) {
            $loader->load($confDir . '/packages/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        }

        $loader->load($confDir . '/services_'. $this->environment. self::CONFIG_EXTS, 'glob');
    }

    /**
     * Returns a loader for the container.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @return DelegatingLoader The loader
     * @throws Exception        Ошибки контейнера.
     *
     * @since 06.11.2020
     */
    private function getContainerLoader(ContainerBuilder $container): DelegatingLoader
    {
        $locator = new FileLocator(static::$containerBuilder->get('kernel'));

        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpLoaderSettingsBitrix($container, $locator),
            new PhpFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }

    /**
     * Статический фасад получение контейнера.
     *
     * @param string $method Метод. В данном случае instance().
     * @param mixed  $args   Аргументы (конфигурационный файл).
     *
     * @return mixed | void
     * @throws Exception Ошибки контейнера.
     */
    public static function __callStatic(string $method, $args = null)
    {
        if ($method === 'instance') {
            if (static::$containerBuilder !== null) {
                return static::$containerBuilder;
            }

            $self = new static(...$args);

            return $self->container();
        }
    }
}
