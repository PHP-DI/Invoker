<?php

namespace Invoker\ParameterResolver;

use ReflectionParameter;

/**
 * Add values for the variadic argument
 *
 * @see http://php.net/manual/functions.arguments.php#functions.variable-arg-list
 */
class VariadicResolver extends GeneratorResolver
{
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct($this);
    }

    /**
     * @param ReflectionParameter $parameter
     * @param array                $provided
     *
     * @return \Generator
     */
    public function __invoke(ReflectionParameter $parameter, array $provided)
    {
        if ($parameter->isVariadic() && array_key_exists($parameter->getName(), $provided)) {
            foreach (array_values((array)$provided[$parameter->getName()]) as $value) {
                yield $value;
            }
        }
    }
}
