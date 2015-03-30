<?php

namespace Invoker\ParameterResolver;

use ReflectionException;
use ReflectionFunctionAbstract;

/**
 * Finds the default value for a parameter, *if it exists*.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DefaultValueResolver implements ParameterResolver
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

            if ($parameter->isOptional()) {
                try {
                    $resolvedParameters[$index] = $parameter->getDefaultValue();
                } catch (ReflectionException $e) {
                    // Can't get default values from PHP internal classes and functions
                }
            }
        }

        return $resolvedParameters;
    }
}
