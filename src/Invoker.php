<?php

namespace Invoker;

use Interop\Container\ContainerInterface;
use Invoker\Exception\NotCallableException;
use Invoker\Exception\NotEnoughParametersException;
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
     * {@inheritdoc}
     */
    public function call($callable, array $parameters = array())
    {
        if ($this->container) {
            $callable = $this->resolveCallableFromContainer($callable);
        }

        if (! is_callable($callable)) {
            throw new NotCallableException(sprintf(
                '%s is not a callable',
                is_object($callable) ? 'Instance of ' . get_class($callable) : var_export($callable, true)
            ));
        }

        $callableReflection = CallableReflection::create($callable);

        $args = $this->parameterResolver->getParameters($callableReflection, $parameters, array());

        // Sort by array key because call_user_func_array ignores numeric keys
        ksort($args);

        // Check all parameters are resolved
        $diff = array_diff_key($callableReflection->getParameters(), $args);
        if (! empty($diff)) {
            /** @var \ReflectionParameter $parameter */
            $parameter = reset($diff);
            throw new NotEnoughParametersException(sprintf(
                'Unable to invoke the callable because no value was given for parameter %d ($%s)',
                $parameter->getPosition() + 1,
                $parameter->name
            ));
        }

        return call_user_func_array($callable, $args);
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
     * @return ContainerInterface|null
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param callable|string|array $callable
     * @return callable
     * @throws NotCallableException
     */
    private function resolveCallableFromContainer($callable)
    {
        // Shortcut for a very common use case
        if ($callable instanceof \Closure) {
            return $callable;
        }

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
                throw new NotCallableException(sprintf(
                    '%s is neither a callable or a valid container entry',
                    $callable
                ));
            }
        }

        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_array($callable) && is_string($callable[0])) {
            if ($this->container->has($callable[0])) {
                // Replace the container entry name by the actual object
                $callable[0] = $this->container->get($callable[0]);
                return $callable;
            } elseif ($isStaticCallToNonStaticMethod) {
                throw new NotCallableException(sprintf(
                    'Cannot call %s::%s() because %s() is not a static method and "%s" is not a container entry',
                    $callable[0],
                    $callable[1],
                    $callable[1],
                    $callable[0]
                ));
            } else {
                throw new NotCallableException(sprintf(
                    'Cannot call %s on %s because it is not a class nor a valid container entry',
                    $callable[1],
                    $callable[0]
                ));
            }
        }

        // Unrecognized stuff, we let it fail later
        return $callable;
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

            return ! $reflection->isStatic();
        }

        return false;
    }
}
