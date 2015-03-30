<?php

namespace Invoker\ParameterResolver;

use ReflectionFunctionAbstract;

/**
 * Tries to map an associative array (string-indexed) to the parameter names.
 *
 * E.g. `->call($callable, ['foo' => 'bar'])` will inject the string `'bar'`
 * in the parameter named `$foo`.
 *
 * Parameters that are not indexed by a string are ignored.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class AssociativeArrayResolver implements ParameterResolver
{
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

            if (array_key_exists($parameter->name, $providedParameters)) {
                $resolvedParameters[$index] = $providedParameters[$parameter->name];
            }
        }

        return $resolvedParameters;
    }
}
