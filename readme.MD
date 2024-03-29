# Базовый функционал для внедрения Symfony в Битрикс

## Установка

composer.json:

```json
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/proklung/bitrix.core.symfony"
        }
    ]
```

```bash
composer require proklung/bitrix-core-symfony
```

## Инициализация

В `init.php`:

```php
use Prokl\ServiceProvider\ServiceProvider;

$serviceProvider = new ServiceProvider('local/configs/services.yaml');

```

Для обеспечения "преемственности" (похожести) с оригиналом можно задать путь к файлу конфигурации (скажем, `bundles.php`)
бандлов вторым (необязательным) параметром конструктора.

#### Переменные среды

Предполагается, что переменные среды к моменту инициализации контейнера уже загружены тем или иным способом.

Значимые переменные среды: 

- `DEBUG` (булево значение - режим отладки), `APP_DEBUG` - алиас `DEBUG`, но с большим приоритетом
(если одновременно присустствуют `DEBUG` и `APP_DEBUG`, то в дело пойдет значение `APP_DEBUG`).

- `APP_ENV` - код окружения. Если код не задан, то будет проинтерпретировано значение `DEBUG` в смысле - если в режиме отладки,
то окружение `dev`, иначе `prod`.

Если переменные среды не заданы, то с помощью класса `Prokl\ServiceProvider\LoadEnvironment` их можно загрузить.

Скажем, в `init.php`, перед инициализацией контейнера:

```php
    // Параметр конструктора - путь, где лежат файлы .env
    $loader = new \Prokl\ServiceProvider\LoadEnvironment($_SERVER['DOCUMENT_ROOT'] . '/../..');
    $loader->load(); // Загрузка $_ENV
    $loader->process(); // Обработка переменных
```

## Конфигурирование

1) Опция `compile.container` в подтягиваемом конфиге - компилировать ли контейнер в файл. Если не задана, то "нет, не компилировать".
Имеет смысл для окружения, не равного "dev". Т.е. опция управляет дампированием контейнера на проде.

Место, где хранятся дампы контейнеров: `<значение переменной контейнера kernel.cache_dir>/<SITE_ID>/containers` 

#### Пути к кэшу и логам

Определяются классом `AppKernel`. По умолчанию:

- путь к кэшу (`kernel.cache_dir`) - `/bitrix/cache`
- путь к логам (`kernel.logs_dir`) - `'/../../logs'` (два уровня выше DOCUMENT_ROOT - особенности используемой
сборки Битрикс)

Чтобы это изменить нужно отнаследоваться от класса `AppKernel` и переопределить несколько переменных:

```php
use Prokl\ServiceProvider\Services\AppKernel;

class MyKernel extends AppKernel
{
   protected $cacheDir = '/bitrix/cache/mycache';
    
   protected $logDir = '/logs-saver';
}
```
(второй вариант - отнаследоваться от `AppKernel` и переопределить методы `getCacheDir` и `getLogDir`).

Изменить через наследование класс ядра:

```php
class MyServiceProvider extends ServiceProvider
{
    protected $kernelServiceClass = MyKernel::class;

    protected $cacheDir = '/bitrix/cache/mycache';

}
```

Второй вариант - отнаследоваться от `ServiceProvider` и заменить метод `getPathCacheDirectory` своей логикой.

## Поддержка бандлов

Файл конфигурации - `/config/standalone_bundles.php`. Этот путь можно изменить через конструктор.

Папка, где лежат конфигурации - `/local/configs`. Конфигурации бандлов - `/local/configs/packages`. 

#### Проблема с приватными сервисами

Согласно концепции Symfony все сервисы (в идеале) должны быть приватными и инжектиться. Но в кастомном случае 
часто нужно получать их через хелпер-сервис-локатор. Для превращения нужных сервисов в публичные предлагается
такое решение. В общем разделе параметров контейнера появилась опция `publicable_services`:

```yaml
parameters:
  publicable_services:
    - 'snc_redis.default'
```

После компиляции контейнера приватный сервис `snc_redis.default` станет публичным.

## Сепаратные микро-контейнеры

Отдельные контейнеры - со своим конфигом, полностью изолированные (для модулей и т.п.).

```php
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Prokl\ServiceProvider\Micro\AbstractStandaloneServiceProvider;
use Prokl\ServiceProvider\Micro\ExampleAppKernel;

class ExampleMicroServiceProvider extends AbstractStandaloneServiceProvider
{
    /**
     * @var ContainerBuilder $containerBuilder Контейнер.
     */
    protected static $containerBuilder;

    /**
     * @var string $pathBundlesConfig Путь к конфигурации бандлов.
     */
    protected $pathBundlesConfig = '/src/Micro/example.config/standalone_bundles.php';

    /**
     * @var string $configDir Папка, где лежат конфиги.
     */
    protected $configDir = '/src/Micro/example.config/example.config/example.yaml';

     /**
     * @var string $kernelServiceClass Класс, реализующий сервис kernel.
     * Нужен для того, чтобы экземпляры контейнеров в kernel сервисе не перемешивались.
     */
    protected $kernelServiceClass = ExampleAppKernel::class;

}
```

Пример класса `ExampleAppKernel`:

```php
use Prokl\ServiceProvider\Micro\AbstractKernel;

class ExampleAppKernel extends AbstractKernel
{
    protected static $kernelContainer;
}
```

Где надо - инициализация:

```php
$micro = new ExampleMicroServiceProvider('src/SymfonyDI/Micro/example.config/example.yaml');
```

Хэлпер `container` заточен под работу с микро-сервис-провайдерами:

```php
var_dump(container($micro)->getParameter('example'));
```

## Автозапуск сервисов

Чтобы сервис запустился автоматически после инициализации контейнера, он должен быть помечен тэгом `service.bootstrap`.

```yaml
  app.options:
    class: Prokl\Services\AppOptions
    arguments: ['%kernel.environment%', '@parameter_bag']
    tags: ['service.bootstrap']
```

Поддерживается приоритет запуска. Тогда надо так:

```yaml
  app.options:
    class: Local\Services\AppOptions
    arguments: ['%kernel.environment%', '@parameter_bag']
    tags: 
      - { name: 'service.bootstrap', priority: 100 }
```

Сервис с приоритетом 100 запустится раньше сервиса с приоритетом 200.

## Автоматическая подвязка на события Битрикс

Тэг: `bitrix.events.init`.

1) `event` - название события.
2) `method` - метод-обработчик в сервисе
3) `module` - модуль события
4) `sort` - сортировка

```yaml
  admin_entity_edit.event_init:
    class: Local\Bitrix\PsModuleInitializer
    tags:
      - { name: bitrix.events.init, module: ps.d7, event: onGetEntityList, method: registerEntities, sort: 0 }
```

## Автоматическое подхватывание расширений Twig

Тэг `twig.extension`.

```yaml
  service.twig.parameter:
    class: Prokl\Bundles\ParameterBundle\Twig\ParameterExtension
    public: true
    arguments:
      - '@service.parameter'
    tags:
      - { name: twig.extension }
```

## Сервисы по умолчанию

Автоматом регистрируются сервисы:

- `service_container` (и alias) - сервис-контейнер целиком
- `app.request` - конвертор глобалов в Request
- синонимы сервиса `kernel`
- `delegated_container_manipulator` - манипулятор делегированными контейнерами.
- `bitrix.request.instance` - Экземпляр битриксового Request
- `bitrix.response.instance` - Экземпляр битриксового Response
- `bitrix.request` - Symfony Request, полученный из битриксового
- `bitrix.request.psr7` - Битриксовый Request, приведенный к PSR-7
- `bitrix.response` - Symfony Response, полученный из битриксового
- `bitrix.response.psr7` - Битриксовый Response, приведенный к PSR-7
- `psr17.http_factory` - HttpFactory стандарта PSR-17
- `psr18.http_client` - Http client стандарта PSR-18

## Хэлперы

1) `container()` - отдает экземпляр контейнера (выступает в роли сервис-локатора):

```php
$kernel = container()->get('kernel');
```

2) `delegatedContainer()` - отдает экземпляр манипулятора (реализующего интерфейс `Symfony\Component\DependencyInjection\ContainerInterface`) 
делегированными контейнерами.

```php
$moduleService = delegatedContainer()->get('my_module_id.service');
```

В контейнере делегированный контейнер помечается тэгом `delegated.container` (их может быть сколь угодно много):

```yaml
  module_notifier_container:
    class: Symfony\Component\DependencyInjection\ContainerInterface
    factory: ['Proklung\Notifier\DI\Services', 'getInstance']
    tags:
      - { name: 'delegated.container' }
```

Делегированный контейнер - автономный контейнер, сформированные в модуле, плагине и тому подобных местах.

## Импорт в контейнер сервисов битриксового сервис-локатора

Автоматом подтягиваются в контейнер сервисы из битриксового сервис-локатора. [Формат](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=14032),
секция `services` из `/bitrix/.settings.php` и `/bitrix/.settings_extra.php`. Также загрузчик пробегает
по списку установленных модулей и подцепляет их тоже.

Для отдельных сервис-контейнеров (отнаследованных от `AbstractStandaloneServiceProvider`) такая загрузка
не производится.

Если эта фича не нужна, то нужно отнаследоваться от `ServiceProvider` и заглушить метод `loadBitrixServiceLocatorConfigs`.

```php
class MyServiceProvider extends ServiceProvider
{
    /**
     * {@inheritDoc}
     */
    protected function loadBitrixServiceLocatorConfigs(DelegatingLoader $loader) : void
    {
    }
}
```

### BitrixSettingsDiAdapter

Адаптер-импортер настроек битриксового сервис-локатора (`.settings.php`) в симфонический контейнер.

- **`importParameters(ContainerInterface $container, array $settings, ?string $section = null)`** - импорт параметров.
  `section` - если задано, то параметры лягут в именованную секцию параметров контейнера.  
- **`importServices(ContainerInterface $container, array $services)`** - импорт сервисов. [Формат](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=14032)

Считываются конфиги в Битриксе как-то так:

```php
use Bitrix\Main\Config\Configuration;

$this->config = Configuration::getInstance()->get('my_config') ?? [];
// Из модуля
$this->parameters = Configuration::getInstance('my.module')->get('parameters') ?? [];
$this->services = Configuration::getInstance('my.module')->get('services') ?? [];
```

### Совместимость с новым механизмом битриксовых роутов

С версии `21.400.0` (от 16.07.2021) главного модуля в Битриксе появился [сносный](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=013764&LESSON_PATH=3913.3516.5062.13764) роутер.

Чтобы использовать в этом контексте контейнер нужно:

- В файле описания маршрутов (например, `/local/routes/web.php`) в самом верху подключить ядро. 

Это важно, т.к. без этого сервис-провайдер завалится на стадии подключения файла с роутами; они подключаются раньше инициализации ядра.
И, если эту проблему еще можно решить, отключив проверку классов сервисов на существование, то запускающиеся автоматом сервисы по тэгу
`service.bootstrap` обломятся стопроцентно.

```php
use Local\ExampleBitrixActionController;
use Prokl\ServiceProvider\ServiceProvider;
use Bitrix\Main\Routing\Controllers\PublicPageController;
use Bitrix\Main\Routing\RoutingConfigurator;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$container = ServiceProvider::instance();

return function (RoutingConfigurator $routes) use ($container) {

    $routes->get('/countries3/{country}/', [$container->get(ExampleBitrixActionController::class), 'cacheAction'])
            ->default('country', 'Russia')
            ->name('first_bitrix_route')
    ;
    
    $routes->get('/', new PublicPageController('/index.php')); // Старый роут на статике.

};
```

Класс битриксового контроллера (`ExampleBitrixActionController`) с заточкой под DI:

```php
namespace Local;

use Bitrix\Main\Engine\Contract\RoutableAction;
use Bitrix\Main\Engine\Controller;
use Symfony\Component\HttpKernel\KernelInterface;

class ExampleBitrixActionController extends Controller implements RoutableAction
{
    /**
     * @var KernelInterface $kernel
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct();
    }

    /**
     * @return string|Controller
     */
    public static function getControllerClass() {
        return ExampleBitrixActionController::class;
    }

    /**
     * @return string
     */
    public static function getDefaultName() {
        return 'testingAction';
    }

    public function cacheAction(string $country)
    {
        return ['cacheDir' => $this->kernel->getCacheDir(), 'country' => $country];
    }

    public function configureActions()
    {
        return [
            'cache' => [
                'prefilters' => [], 'postfilters' => [],
            ],
        ];
    }
}
```
Описывается сервисом так:

```yaml
  Local\ExampleBitrixActionController:
    arguments: ['@kernel']
```

Ничего революционного, но так можно получить нормально-сконфигурированный класс контроллера, 
со всякими зависимостями и т.п.