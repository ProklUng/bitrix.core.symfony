<?php

namespace Prokl\ServiceProvider\CompilePasses;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class AddTaggedCompilerPass
 * @package Prokl\ServiceProvider\CompilePasses
 *
 * @since 12.03.2021
 * @see https://github.com/paysera/lib-dependency-injection
 *
 * @example
 *
 * class SomeBundle extends Bundle
 * {
 * public function build(ContainerBuilder $container)
 * {
 *      $container->addCompilerPass(new AddTaggedCompilerPass(
 *                          'some_bundle.registry', // ID of service to modify
 *                          'my_provider',          // name of tag to search for
 *                          'addProvider',          // method to call on modified service
 * [  // this parameter is optional and defines attributes to pass from tag
 * 'key',
 * 'theme' => 'default',   // attribute with default value
 * 'optional',
 * ]
 * ));
 * } }
 */
class AddTaggedCompilerPass implements CompilerPassInterface
{
    /**
     * Calls method passing tagged service.
     */
    public const CALL_MODE_SERVICE = 'service';

    /**
     * Calls method passing tagged service, but also marks tagged services as lazy.
     */
    public const CALL_MODE_LAZY_SERVICE = 'lazy_service';

    /**
     * Calls method passing only tagged service ID. Makes tagged services public.
     */
    public const CALL_MODE_ID = 'id';

    /**
     * @var string $collectorServiceId
     */
    private $collectorServiceId;

    /**
     * @var string $tagName
     */
    private $tagName;

    /**
     * @var string $methodName
     */
    private $methodName;

    /**
     * @var array $parameters
     */
    private $parameters;

    /**
     * @var string $callMode
     */
    private $callMode;

    /**
     * @var string|null $priorityAttribute
     */
    private $priorityAttribute;

    /**
     * AddTaggedCompilerPass constructor.
     *
     * @param string $collectorServiceId
     * @param string $tagName
     * @param string $methodName
     * @param array  $parameters
     */
    public function __construct(
        string $collectorServiceId,
        string $tagName,
        string $methodName,
        array $parameters = []
    ) {
        $this->collectorServiceId = $collectorServiceId;
        $this->tagName = $tagName;
        $this->methodName = $methodName;
        $this->parameters = $parameters;
        $this->callMode = self::CALL_MODE_SERVICE;
    }

    /**
     * If enabled, tags will be ordered by priority before initiating method calls.
     * Lower priority means called earlier.
     * If no priority provided, defaults to 0.
     *
     * @param string $priorityAttribute Priority attribute.
     *
     * @return $this
     */
    public function enablePriority(string $priorityAttribute = 'priority'): self
    {
        $this->priorityAttribute = $priorityAttribute;

        return $this;
    }

    /**
     * Sets call mode to one of CALL_MODE_* constants
     *
     * @param string $callMode Call mode.
     *
     * @return $this
     */
    public function setCallMode(string $callMode): self
    {
        $this->callMode = $callMode;
        return $this;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     * @throws InvalidConfigurationException When no such service.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition($this->collectorServiceId)) {
            throw new InvalidConfigurationException('No such service: ' . $this->collectorServiceId);
        }

        $definition = $container->getDefinition($this->collectorServiceId);
        $tags = $this->collectTags($container->findTaggedServiceIds($this->tagName));
        foreach ($tags as $tag) {
            $arguments = array_merge(
                [$this->getServiceArgument($container, $tag['service_id'])],
                $this->collectAdditionalArguments($tag['attributes'], $tag['service_id'])
            );
            $definition->addMethodCall($this->methodName, $arguments);
        }
    }

    /**
     * @param array $tagsByServiceId
     *
     * @return array
     */
    private function collectTags(array $tagsByServiceId): array
    {
        $tags = [];
        foreach ($tagsByServiceId as $serviceId => $tagsInsideService) {
            foreach ($tagsInsideService as $tagAttributes) {
                $tags[] = [
                    'service_id' => $serviceId,
                    'attributes' => $tagAttributes,
                ];
            }
        }

        return $this->prioritizeTags($tags);
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    private function prioritizeTags(array $tags) : array
    {
        if ($this->priorityAttribute === null) {
            return $tags;
        }

        usort(
            $tags,
            /**
             * @param array $tag1
             * @param array $tag2
             *
             * @return mixed
             */
            function (array $tag1, array $tag2) {
                $tag1Priority = $tag1['attributes'][$this->priorityAttribute] ?? 0;
                $tag2Priority = $tag2['attributes'][$this->priorityAttribute] ?? 0;

                return $tag1Priority - $tag2Priority;
            }
        );

        return $tags;
    }

    /**
     * Can be overwritten in extended classes
     *
     * @param ContainerBuilder $container Container.
     * @param string           $id        Service Id.
     *
     * @return mixed returns argument to pass to the collector service
     */
    private function getServiceArgument(ContainerBuilder $container, string $id)
    {
        if ($this->callMode === self::CALL_MODE_ID) {
            $container->getDefinition($id)->setPublic(true);

            return $id;
        }

        if ($this->callMode === self::CALL_MODE_LAZY_SERVICE) {
            $container->getDefinition($id)->setLazy(true);
        }

        return new Reference($id);
    }

    /**
     * @param array  $tagAttributes
     * @param string $serviceId     ID сервиса.
     *
     * @return array
     */
    private function collectAdditionalArguments(array $tagAttributes, string $serviceId): array
    {
        $onlyOptional = false;
        $arguments = [];
        foreach ($this->parameters as $key => $value) {
            if (is_numeric($key)) {
                $name = $value;
                $hasDefault = false;
                $default = null;
            } else {
                $name = $key;
                $hasDefault = true;
                $default = $value;
            }

            $hasAttribute = isset($tagAttributes[$name]);
            if ($hasAttribute && $onlyOptional) {
                throw new InvalidConfigurationException(sprintf(
                    'Some required attributes are missing in service %s tag %s definition',
                    $serviceId,
                    $this->tagName
                ));
            }

            if (!$hasAttribute && !$hasDefault) {
                $onlyOptional = true;
            } else {
                $arguments[] = $tagAttributes[$name] ?? $default;
            }
        }

        return $arguments;
    }
}
