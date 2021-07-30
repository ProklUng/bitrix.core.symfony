<?php

namespace Prokl\ServiceProvider\Utils\DelegatedContainer;

use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Class Prokl\ServiceProvider\Utils\DelegatedContainer
 * @package Local\Services\Delegated
 *
 * @since 30.07.2021
 */
class Manipulator implements ContainerInterface
{
    /**
     * @var array $delegatedContainers Делегированные контейнеры.
     */
    private $delegatedContainers = [];

    /**
     * DelegatedContainerManipulator constructor.
     *
     * @param mixed $delegatingContainers Список делегированных контейнеров.
     * Тэг - delegated.container.
     */
    public function __construct(...$delegatingContainers)
    {
        foreach ($delegatingContainers as $container) {
            $iterator = $container->getIterator();
            $array = iterator_to_array($iterator);
            $this->delegatedContainers[] = $array;
        }
    }

    /**
     * @inheritdoc
     */
    public function set(string $id, ?object $service)
    {
        throw new RuntimeException('Method set not implemented for delegated containers.');
    }

    /**
     * @inheritdoc
     */
    public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        foreach ($this->delegatedContainers as $delegatingContainer) {
            /** @var ContainerInterface[] $delegatingContainer */
            if (!$delegatingContainer[0]) {
                continue;
            }

            if ($delegatingContainer[0]->has($id)) {
                return $delegatingContainer[0]->get($id);
            }
        }

        throw new InvalidArgumentException(
            '%s - request not exist service',
            $id
        );
    }

    /**
     * @inheritdoc
     */
    public function has(string $id)
    {
        foreach ($this->delegatedContainers as $delegatingContainer) {
            /** @var ContainerInterface[] $delegatingContainer */
            if (!$delegatingContainer[0]) {
                continue;
            }

            if ($delegatingContainer[0]->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function initialized(string $id)
    {
        throw new RuntimeException('Method initialized not implemented for delegated containers.');
    }

    /**
     * @inheritdoc
     */
    public function getParameter(string $name)
    {
        foreach ($this->delegatedContainers as $delegatingContainer) {
            /** @var ContainerInterface[] $delegatingContainer */
            if (!$delegatingContainer[0]) {
                continue;
            }

            if ($delegatingContainer[0]->hasParameter($name)) {
                return $delegatingContainer[0]->getParameter($name);
            }
        }

        throw new InvalidArgumentException(
            'Parameter %s not exist in delegated containers',
            $name
        );
    }

    /**
     * @inheritdoc
     */
    public function hasParameter(string $name)
    {
        foreach ($this->delegatedContainers as $delegatingContainer) {
            /** @var ContainerInterface[] $delegatingContainer */
            if (!$delegatingContainer[0]) {
                continue;
            }

            if ($delegatingContainer[0]->hasParameter($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function setParameter(string $name, $value)
    {
        throw new RuntimeException('Method setParameter not implemented for delegated containers.');
    }
}
