<?php

namespace Invoker\ParameterResolver;

use ReflectionParameter;

/**
 * Add values for the variadic argument
 *
 * @see http://php.net/manual/functions.arguments.php#functions.variable-arg-list
 */
class AssociativeVariadicResolver extends GeneratorResolver
{
    /**
     * @var bool
     */
    private $cast;

    /**
     * @inheritdoc
     *
     * @param bool $cast
     */
    public function __construct($cast = true)
    {
        parent::__construct($this);
        $this->cast = $cast;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param array                $provided
     *
     * @return \Generator
     */
    public function __invoke(ReflectionParameter $parameter, array $provided)
    {
        if ($parameter->isVariadic() && array_key_exists($key = $parameter->getName(), $provided)) {
            foreach ($this->cast ? array_values((array)$provided[$key]) : [$provided[$key]] as $value) {
                yield $value;
            }
        }
    }
}
