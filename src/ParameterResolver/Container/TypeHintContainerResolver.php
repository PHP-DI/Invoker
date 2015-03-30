<?php

namespace Invoker\ParameterResolver\Container;

use Interop\Container\ContainerInterface;
use Invoker\ParameterResolver\ParameterResolver;
use ReflectionFunctionAbstract;

/**
 * Inject entries from a DI container using the type-hints.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class TypeHintContainerResolver implements ParameterResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container The container to get entries from.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ) {
        foreach ($reflection->getParameters() as $index => $parameter) {
            if (array_key_exists($index, $resolvedParameters)) {
                // Skip already resolved parameters
                continue;
            }

            $parameterClass = $parameter->getClass();

            if ($parameterClass && $this->container->has($parameterClass->name)) {
                $resolvedParameters[$index] = $this->container->get($parameterClass->name);
            }
        }

        return $resolvedParameters;
    }
}
