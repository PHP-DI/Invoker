<?php

namespace Invoker;

use Interop\Container\ContainerInterface;
use Invoker\ParameterResolver\AssociativeArrayParameterResolver;
use Invoker\ParameterResolver\DefaultValueParameterResolver;
use Invoker\ParameterResolver\NumericArrayParameterResolver;
use Invoker\ParameterResolver\ParameterResolver;
use Invoker\ParameterResolver\ParameterResolverChain;
use Invoker\Reflection\CallableReflection;

/**
 * Invoke a callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Invoker implements InvokerInterface
{
    /**
     * @var ParameterResolver
     */
    private $parameterResolver;

    /**
     * TODO optionally null
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ParameterResolver $parameterResolver = null, ContainerInterface $container)
    {
        $this->parameterResolver = $parameterResolver ?: $this->createParameterResolver();
        $this->container = $container;
    }

    /**
     * Call the given function using the given parameters.
     *
     * @param callable $callable   Function to call.
     * @param array    $parameters Parameters to use.
     *
     * @return mixed Result of the function.
     */
    public function call($callable, array $parameters = array())
    {
        $callableReflection = CallableReflection::create($callable);

        $args = $this->parameterResolver->getParameters($callableReflection, $parameters, array());

        // Sort by array key because invokeArgs ignores numeric keys
        ksort($args);

        if ($callableReflection instanceof \ReflectionFunction) {
            return $callableReflection->invokeArgs($args);
        }

        /** @var \ReflectionMethod $callableReflection */
        if ($callableReflection->isStatic()) {
            // Static method
            $object = null;
        } elseif (is_object($callable)) {
            // Callable object
            $object = $callable;
        } elseif (is_string($callable)) {
            // Callable class (need to be instantiated)
            $object = $this->container->get($callable);
        } elseif (is_string($callable[0])) {
            // Class method
            $object = $this->container->get($callable[0]);
        } else {
            // Object method
            $object = $callable[0];
        }

        return $callableReflection->invokeArgs($object, $args);
    }

    /**
     * Create the default parameter resolver.
     *
     * @return ParameterResolver
     */
    private function createParameterResolver()
    {
        return new ParameterResolverChain(array(
            new NumericArrayParameterResolver,
            new AssociativeArrayParameterResolver,
            new DefaultValueParameterResolver,
        ));
    }
}
