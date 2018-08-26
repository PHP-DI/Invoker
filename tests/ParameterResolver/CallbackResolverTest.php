<?php

namespace Invoker\ParameterResolver;

use Invoker\Reflection\CallableReflection;
use ReflectionParameter;

/**
 */
class CallbackResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function providerGetParameters()
    {
        return [
            'empty' => [[], function() {}],
            'no match' => [[], function() {}, ['a1' => 'A1']],
            'partial' => [
                [1 => 'A2', 2 => [[]]],
                function($a1, $a2, array ...$a3) {}, ['a3' => [[]], 'a2' => 'A2']
            ],
            'resolved' => [
                [2 => 'resolved', 1 => 'A2'],
                function($a1, $a2, $a3) {}, ['a3' => 'A3', 'a2' => 'A2'],
                [2 => 'resolved']
            ],
        ];
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @dataProvider providerGetParameters
     * @covers       CallbackResolver::getParameters
     * @param array $expected
     * @param callable $callable
     * @param array $provided
     * @param array $resolved
     */
    public function testGetParameters(array $expected, callable $callable, array $provided = [], array $resolved = [])
    {
        $resolver = new CallbackResolver(function(array $provided, ReflectionParameter ...$parameters) {
            $resolved = [];
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                if (array_key_exists($name, $provided)) {
                    $resolved[$parameter->getPosition()] = $provided[$name];
                }
            }
            return $resolved;
        });

        $this->assertSame(
            $expected,
            $resolver->getParameters(CallableReflection::create($callable), $provided, $resolved)
        );
    }
}
