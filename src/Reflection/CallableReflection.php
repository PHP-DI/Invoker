<?php declare(strict_types=1);

namespace Invoker\Reflection;

use Invoker\Exception\NotCallableException;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Create a reflection object from a callable.
 */
class CallableReflection
{
    /**
     * @throws NotCallableException|ReflectionException
     */
    public static function create(callable $callable): ReflectionFunctionAbstract
    {
        // Closure
        if ($callable instanceof \Closure) {
            return new ReflectionFunction($callable);
        }

        // Array callable
        if (is_array($callable)) {
            [$class, $method] = $callable;

            if (! method_exists($class, $method)) {
                throw NotCallableException::fromInvalidCallable($callable);
            }

            return new ReflectionMethod($class, $method);
        }

        // Callable object (i.e. implementing __invoke())
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            return new ReflectionMethod($callable, '__invoke');
        }

        // Callable class (i.e. implementing __invoke())
        if (is_string($callable) && class_exists($callable) && method_exists($callable, '__invoke')) {
            return new ReflectionMethod($callable, '__invoke');
        }

        // Standard function
        if (is_string($callable) && function_exists($callable)) {
            return new ReflectionFunction($callable);
        }

        throw new NotCallableException(sprintf(
            '%s is not a callable',
            is_string($callable) ? $callable : 'Instance of ' . get_class($callable)
        ));
    }
}
