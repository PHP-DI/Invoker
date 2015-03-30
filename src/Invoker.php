<?php

namespace Invoker;

use Interop\Container\ContainerInterface;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use Invoker\ParameterResolver\ParameterResolver;
use Invoker\ParameterResolver\ResolverChain;
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
     * @param callable|string|array $callable   Function to call.
     * @param array                 $parameters Parameters to use.
     *
     * @return mixed Result of the function.
     */
    public function call($callable, array $parameters = array())
    {
        if ($this->container) {
            $callable = $this->resolveCallableFromContainer($callable);
        }
        $this->assertIsCallable($callable);

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
        return new ResolverChain(array(
            new NumericArrayResolver,
            new AssociativeArrayResolver,
            new DefaultValueResolver,
        ));
    }

    /**
     * @return ParameterResolver By default it's a ResolverChain
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
        $isStaticCallToNonStaticMethod = false;

        // If it's already a callable there is nothing to do
        if (is_callable($callable)) {
            $isStaticCallToNonStaticMethod = $this->isStaticCallToNonStaticMethod($callable);
            if (! $isStaticCallToNonStaticMethod) {
                return $callable;
            }
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
            } elseif ($isStaticCallToNonStaticMethod) {
                throw new \RuntimeException(sprintf(
                    'Cannot call %s::%s() because %s() is not a static method and "%s" is not a container entry',
                    $callable[0],
                    $callable[1],
                    $callable[1],
                    $callable[0]
                ));
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

    private function assertIsCallable($callable)
    {
        if (! is_callable($callable)) {
            throw new \RuntimeException(sprintf(
                '%s is not a callable',
                is_object($callable) ? 'Instance of ' . get_class($callable) : var_export($callable, true)
            ));
        }
    }

    /**
     * Check if the callable represents a static call to a non-static method.
     *
     * @param mixed $callable
     * @return bool
     */
    private function isStaticCallToNonStaticMethod($callable)
    {
        if (is_array($callable) && is_string($callable[0])) {
            list($class, $method) = $callable;
            $reflection = new \ReflectionMethod($class, $method);

            return !$reflection->isStatic();
        }

        return false;
    }
}
