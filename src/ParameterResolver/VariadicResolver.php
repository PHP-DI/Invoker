<?php

namespace Invoker\ParameterResolver;

use ReflectionParameter;

/**
 * Add values for the variadic argument
 *
 * @see http://php.net/manual/functions.arguments.php#functions.variable-arg-list
 */
class VariadicResolver extends CallbackResolver
{
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct($this);
    }

    /**
     * @param array               $provided
     * @param ReflectionParameter ...$parameters
     *
     * @return array
     */
    public function __invoke(array $provided, ReflectionParameter ...$parameters)
    {
        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic() && array_key_exists($parameter->getName(), $provided)) {
                $resolved = [];
                foreach (array_values((array)$provided[$parameter->getName()]) as $i => $value) {
                    $resolved[$parameter->getPosition() + $i] = $value;
                }
                return $resolved;
            }
        }
        return [];
    }
}
