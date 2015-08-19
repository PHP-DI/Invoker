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
use ReflectionFunctionAbstract;

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

    /**
     * @var CallableResolver|null
     */
    private $callableResolver;

    public function __construct(ParameterResolver $parameterResolver = null, ContainerInterface $container = null)
    {
        $this->parameterResolver = $parameterResolver ?: $this->createParameterResolver();
        $this->container = $container;

        if ($container) {
            $this->callableResolver = new CallableResolver($container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function call($callable, array $parameters = array())
    {
        if ($this->callableResolver) {
            $callable = $this->callableResolver->resolve($callable);
        }

        if (! is_callable($callable)) {
            throw new NotCallableException(sprintf(
                '%s is not a callable',
                is_object($callable) ? 'Instance of ' . get_class($callable) : var_export($callable, true)
            ));
        }

        $callableReflection = CallableReflection::create($callable);

        $args = $this->parameterResolver->getParameters($callableReflection, $parameters, array());

        $this->assertMandatoryParametersAreResolved($args, $callableReflection);

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
     * @return ContainerInterface|null
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return CallableResolver|null Returns null if no container was given in the constructor.
     */
    public function getCallableResolver()
    {
        return $this->callableResolver;
    }

    private function assertMandatoryParametersAreResolved($parameters, ReflectionFunctionAbstract $reflection)
    {
        $parameterCount = $reflection->getNumberOfRequiredParameters();

        // TODO is there a more efficient way?
        for ($i = 0; $i < $parameterCount; $i++) {
            if (! array_key_exists($i, $parameters)) {
                $reflectionParameters = $reflection->getParameters();
                $parameter = $reflectionParameters[$i];

                throw new NotEnoughParametersException(sprintf(
                    'Unable to invoke the callable because no value was given for parameter %d ($%s)',
                    $i + 1,
                    $parameter->name
                ));
            }
        }
    }
}
