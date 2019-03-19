<?php

namespace Invoker\ParameterResolver;

use Generator;
use ReflectionParameter;

/**
 * Add values for the typed variadic argument
 *
 * class    -> function(Class ...$args) {}
 * array    -> function(array ...$args) {}
 * callable -> function(callable ...$args) {}
 *
 * @see http://php.net/manual/functions.arguments.php#functions.variable-arg-list
 */
class TypeHintVariadicResolver extends GeneratorResolver
{
    /**
     * @var bool
     */
    private $onlyNumericKeys;

    /**
     * @inheritdoc
     *
     * @param bool $onlyNumericKeys
     */
    public function __construct($onlyNumericKeys = false)
    {
        parent::__construct($this);
        $this->onlyNumericKeys = $onlyNumericKeys;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param array                $provided
     *
     * @return Generator
     */
    public function __invoke(ReflectionParameter $parameter, array $provided)
    {
        if ($parameter->isVariadic()) {
            $class         = $parameter->getClass();
            $checkArray    = $parameter->isArray();
            $checkCallable = $parameter->isCallable();
            foreach ($provided as $key => $value) {
                if ($this->onlyNumericKeys && !is_numeric($key)) {
                    continue;
                }
                $ok = ($class && is_object($value) && $class->isInstance($value))
                    || ($checkArray && is_array($value))
                    || ($checkCallable && is_callable($value));

                if ($ok) {
                    yield $value;
                }
            }
        }
    }
}
