<?php

namespace Prokl\ServiceProvider\Extra;

use Exception;
use Prokl\ServiceProvider\Extra\Contract\ExtraFeatureServiceProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\DependencyInjection\CachePoolClearerPass;
use Symfony\Component\Cache\DependencyInjection\CachePoolPass;
use Symfony\Component\Cache\DependencyInjection\CachePoolPrunerPass;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Class ExtraFeature
 * @package Prokl\ServiceProvider\Extra
 *
 * @since 28.11.2020
 * @since 20.03.2021 Единая точка входа. Все регистраторы стали приватными.
 */
class ExtraFeature implements ExtraFeatureServiceProviderInterface
{
    /**
     * @var boolean $annotationsConfigEnabled Использовать ли аннотации в роутере.
     */
    private $annotationsConfigEnabled = false;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function register(ContainerBuilder $containerBuilder) : void
    {
    }

    /**
     * PropertyInfo.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @throws LogicException
     */
    private function registerPropertyInfoConfiguration(ContainerBuilder $container): void
    {
        if (!interface_exists(PropertyInfoExtractorInterface::class)) {
            throw new LogicException(
                'PropertyInfo support cannot be enabled as the PropertyInfo component is not installed. 
                Try running "composer require symfony/property-info".'
            );
        }

        if (interface_exists('phpDocumentor\Reflection\DocBlockFactoryInterface')) {
            $definition = $container->register('property_info.php_doc_extractor',
                'Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor');
            $definition->setPrivate(true);
            $definition->addTag('property_info.description_extractor', ['priority' => -1000]);
            $definition->addTag('property_info.type_extractor', ['priority' => -1001]);
        }
    }
}
