<?php

namespace Invoker;

use Closure;
use Invoker\Exception\NotCallableException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;

/**
 * Resolves a callable from a container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class CallableResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Resolve the given callable into a real PHP callable.
     *
     * @param callable|string|array $callable
     *
     * @return callable Real PHP callable.
     *
     * @throws NotCallableException|ReflectionException
     */
    public function resolve($callable): callable
    {
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable, 2);
        }

        $callable = $this->resolveFromContainer($callable);

        if (! is_callable($callable)) {
            throw NotCallableException::fromInvalidCallable($callable, true);
        }

        return $callable;
    }

    /**
     * @param callable|string|array $callable
     * @return callable|mixed
     * @throws NotCallableException|ReflectionException
     */
    private function resolveFromContainer($callable)
    {
        // Shortcut for a very common use case
        if ($callable instanceof Closure) {
            return $callable;
        }
        if (\is_string($callable) && \class_exists($callable) && \method_exists($callable, '__invoke')) {
            return new $callable;
        }
        if (\is_array($callable) && \count($callable) === 2) {
            list($class, $parameters) = $callable;
            if (\is_string($class) && \class_exists($class) && \method_exists($class, '__callStatic')) {
                return \Closure::fromCallable(function (...$arg) use ($class, $parameters) {
                    if (!is_array($parameters)) {
                        $parameters = [$parameters];
                    }
                    \array_push($parameters, $arg);
                    return \forward_static_call_array([$class, '__callStatic'], $parameters);
                });
            }
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
            try {
                return $this->container->get($callable);
            } catch (NotFoundExceptionInterface $e) {
                if ($this->container->has($callable)) {
                    throw $e;
                }
                throw NotCallableException::fromInvalidCallable($callable, true);
            }
        }

        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_array($callable) && is_string($callable[0])) {
            try {
                // Replace the container entry name by the actual object
                $callable[0] = $this->container->get($callable[0]);
                return $callable;
            } catch (NotFoundExceptionInterface $e) {
                if ($this->container->has($callable[0])) {
                    throw $e;
                }
                if ($isStaticCallToNonStaticMethod) {
                    throw new NotCallableException(sprintf(
                        'Cannot call %s::%s() because %s() is not a static method and "%s" is not a container entry',
                        $callable[0],
                        $callable[1],
                        $callable[1],
                        $callable[0]
                    ));
                }
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
     *
     * @throws ReflectionException
     */
    private function isStaticCallToNonStaticMethod($callable): bool
    {
        if (is_array($callable) && is_string($callable[0])) {
            [$class, $method] = $callable;
            $reflection = new ReflectionMethod($class, $method);

            return ! $reflection->isStatic();
        }

        return false;
    }
}
