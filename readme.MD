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

## Конфигурирование

1) Опция `compile.container` в подтягиваемом конфиге - компилировать ли контейнер в файл. Если не задана, то "нет, не компилировать".
Имеет смысл для окружения, не равного "dev". Т.е. опция управляет дампированием контейнера на проде.

Место, где хранятся дампы контейнеров: `/bitrix/cache/<SITE_ID>/containers` 

## Поддержка бандлов

Файл конфигурации - `/config/standalone_bundles.php`. Этот путь можно изменить через конструктор.

Папка, где лежат конфигурации - `/local/configs`. Конфигурации бандлов - `/local/configs/packages`. 

## Сепаратные микро-контейнеры

Отдельные контейнеры - со своим конфигом, полностью изолированные (для модулей и т.п.).

```php
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Prokl\ServiceProvider\Micro\AbstractStandaloneServiceProvider;

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