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
     * @var ContainerInterface|null
     */
    private $container;

    public function __construct(ParameterResolver $parameterResolver = null, ContainerInterface $container = null)
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
        if ($this->container) {
            $callable = $this->resolveCallableFromContainer($callable);
        }

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

    /**
     * @return ParameterResolver By default it's a ParameterResolverChain
     */
    public function getParameterResolver()
    {
        return $this->parameterResolver;
    }

    /**
     * @param callable|string|array $callable
     * @return callable
     */
    private function resolveCallableFromContainer($callable)
    {
        // If it's already a callable there is nothing to do
        if (is_callable($callable)) {
            return $callable;
        }

        // The callable is a container entry name
        if (is_string($callable)) {
            if ($this->container->has($callable)) {
                return $this->container->get($callable);
            } else {
                throw new \RuntimeException(sprintf(
                    '%s is neither a callable or a valid container entry',
                    $callable
                ));
            }
        }

        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_array($callable) && is_string($callable[0])) {
            if ($this->container->has($callable[0])) {
                $callable[0] = $this->container->get($callable[0]);
                return $callable;
            } else {
                throw new \RuntimeException(sprintf(
                    'Cannot call %s on %s because it is not a class nor a valid container entry',
                    $callable[1],
                    $callable[0]
                ));
            }
        }

        // Unrecognized stuff, we let it fail later
        return $callable;
    }
}
