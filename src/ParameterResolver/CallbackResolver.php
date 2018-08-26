<?php

namespace Invoker\ParameterResolver;

use ReflectionFunctionAbstract;

/**
 * Use given callback to resolve parameters
 *
 * This class implements the "middleware" approach:
 * - already resolved parameters are NOT PASSED to the callback
 * - the result of the callback is MERGED INTO the array of resolved parameters
 */
class CallbackResolver implements ParameterResolver
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * The callback should accept an array of provided parameters as first argument
     * and afterwards a variadic number of ReflectionParameter instances.
     *
     * @param callable $callback function(array $provided, \ReflectionParameter ...$parameters): array
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     *
     * Find unresolved parameters and pass them to the callback.
     * Add values, resolved by the the callback, to the result
     * and return aggregated resolved parameters.
     */
    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ) {
        $parameters = $reflection->getParameters();
        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }
        return $resolvedParameters + call_user_func($this->callback, $providedParameters, ...$parameters);
    }
}
