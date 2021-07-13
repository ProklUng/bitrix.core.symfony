<?php

namespace Prokl\ServiceProvider\Tests\Cases;

use Exception;
use Prokl\BitrixTestingTools\Base\BitrixableTestCase;
use Prokl\ServiceProvider\ServiceProvider;
use Prokl\ServiceProvider\Services\AppKernel;
use Prokl\ServiceProvider\Utils\Loaders\PhpLoaderSettingsBitrix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * Class PhpLoaderSettingsBitrixTest
 * @package Prokl\ServiceProvider\Tests\Cases
 *
 * @since 13.07.2021
 */
class PhpLoaderSettingsBitrixTest extends BitrixableTestCase
{
    /**
     * @var PhpLoaderSettingsBitrix $obTestObject
     */
    protected $obTestObject;

    /**
     * @var ContainerBuilder $dummyContainer
     */
    private $dummyContainer;

    /**
     * @var AppKernel
     */
    private $kernel;

    /**
     * @var string $pathYamlConfig Путь к конфигу.
     */
    private $fixture = '/../../../../tests/Fixtures/Settings';

    /**
     * @var string $pathYamlConfig Путь к конфигу.
     */
    private $pathYamlConfig = '../../../../tests/Fixtures/config/test_container.yaml';

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['DEBUG'] = true;
        $this->container = new ServiceProvider(
            $this->pathYamlConfig
        );

        $this->dummyContainer = $this->container->container();

        $this->kernel = $this->dummyContainer->get('kernel');
        $locator = new FileLocator($this->kernel);

        $this->obTestObject = new PhpLoaderSettingsBitrix(
            $this->dummyContainer,
            $locator
        );
    }

    /**
     * supports().
     *
     * @param string $file Файл.
     *
     * @return void
     *
     * @dataProvider dataProviderValidBitrixConfigFilename
     */
    public function testSupports(string $file) : void
    {
        $result = $this->obTestObject->supports($file);

        $this->assertTrue($result, 'Валидный конфиг не прошел проверку.');
    }

    /**
     * @return array
     */
    public function dataProviderValidBitrixConfigFilename() : array
    {
        return [
            [$_SERVER['DOCUMENT_ROOT'] . $this->fixture . '/.settings.php'],
            [$_SERVER['DOCUMENT_ROOT'] . $this->fixture . '/.settings_extra.php']
        ];
    }

    /**
     * supports(). Невалидные файлы.
     *
     * @param string $file Файл.
     *
     * @return void
     *
     * @dataProvider dataProviderInvalidBitrixConfigFilename
     */
    public function testSupportsInvalid(string $file) : void
    {
        $result = $this->obTestObject->supports($file);

        $this->assertFalse($result, 'Невалидный конфиг прошел проверку.');
    }

    /**
     * @return array
     */
    public function dataProviderInvalidBitrixConfigFilename() : array
    {
        return [
            [$_SERVER['DOCUMENT_ROOT'] . $this->fixture . '/.config.php'],
            [$_SERVER['DOCUMENT_ROOT'] . $this->fixture . '/.settings.html'],
            [$_SERVER['DOCUMENT_ROOT'] . $this->fixture . '/.settings_extra'],
        ];
    }
}
