<?php

namespace Invoker\ParameterResolver;

use ReflectionFunctionAbstract;

/**
 * 1. Skip parameters already resolved
 * 2. Iterate over unresolved parameters and resolve them with the provided generator
 * 3. Add currently resolved parameters to the previous result
 */
class GeneratorResolver implements ParameterResolver
{
    /**
     * @var callable
     */
    private $generator;

    /**
     * @param callable $generator function(\ReflectionParameter $parameter, array $provided = []): array
     */
    public function __construct(callable $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @inheritdoc
     */
    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ) {
        // 1. Skip parameters already resolved
        $parameters = $reflection->getParameters();
        if (!empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }

        // 2. Iterate over unresolved parameters and resolve them with the provided generator
        $resolvedByGenerator = [];
        foreach ($parameters as $index => $parameter) {
            foreach (call_user_func($this->generator, $parameter, $providedParameters) as $value) {
                $resolvedByGenerator[$index] = $value;
            }
        }

        // 3. Add currently resolved parameters to the previous result
        return $resolvedParameters + $resolvedByGenerator;
    }
}
