<?php

namespace Invoker\ParameterResolver;

use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\Reflection\CallableReflection;
use Invoker\Test\Mock\ArrayContainer;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionParameter;

/**
 * @requires PHP 5.6
 * @coversDefaultClass GeneratorResolver
 */
class GeneratorResolverTest extends TestCase
{
    /**
     * @return array
     */
    public function providerSameBehaviour()
    {
        return [
            'assoc' => [
                [0 => 'A1', 2 => 'A3'],
                $this->resolvers(AssociativeArrayResolver::class),
                function($a1, $a2, $a3) {},
                ['a1' => 'A1', 'a3' => 'A3'],
            ],
            'assoc-container' => [
                [1 => 'A2', 2 => 'A3'],
                $this->resolvers(ParameterNameContainerResolver::class, ['a3' => 'A3', 'a2' => 'A2']),
                function($a1, $a2, $a3) {},
            ],
            'numeric' => [
                [1 => 'A2'],
                $this->resolvers(NumericArrayResolver::class),
                function($a1, $a2, $a3) {},
                ['a1' => 'A1', 'a3' => 'A3', 1 => 'A2'],
            ],
            'type' => [
                [1 => 'self', 2 => 'parent'],
                $this->resolvers(TypeHintResolver::class),
                function($a1, self $a2, TestCase $a3) {},
                [self::class => 'self', TestCase::class => 'parent'],
            ],
            'type-container' => [
                [0 => 'self', 2 => 'parent'],
                $this->resolvers(TypeHintContainerResolver::class, [self::class => 'self', TestCase::class => 'parent']),
                function(self $a1, $a2, TestCase $a3) {},
            ],
            'default' => [
                [1 => ['array'], 2 => 'string'],
                $this->resolvers(DefaultValueResolver::class),
                function($a1, array $a2 = ['array'], $a3 = 'string') {},
            ],
        ];
    }

    /**
     * @covers ::getParameters
     * @dataProvider providerSameBehaviour
     *
     * @param array               $expected
     * @param ParameterResolver[] $resolvers
     * @param callable            $callable
     * @param array               $provided
     * @param array               $resolved
     */
    public function testSameBehaviour(
        array $expected,
        array $resolvers,
        callable $callable,
        array $provided = [],
        array $resolved = []
    ) {
        $reflection = CallableReflection::create($callable);
        foreach ($resolvers as $type => $resolver) {
            $this->assertSame($expected, $resolver->getParameters($reflection, $provided, $resolved), $type);
        }
    }

    /**
     * @param string $class
     * @param array $entries
     *
     * @return array
     */
    private function resolvers($class, array $entries = [])
    {
        $container = new ArrayContainer($entries);
        return [
            'class'     => $this->classes($container)[$class],
            'generator' => $this->generators($container)[$class],
        ];
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ParameterResolver[]
     */
    private function classes(ContainerInterface $container)
    {
        return [
            AssociativeArrayResolver::class       => new AssociativeArrayResolver,
            NumericArrayResolver::class           => new NumericArrayResolver,
            TypeHintResolver::class               => new TypeHintResolver,
            DefaultValueResolver::class           => new DefaultValueResolver,
            TypeHintContainerResolver::class      => new TypeHintContainerResolver($container),
            ParameterNameContainerResolver::class => new ParameterNameContainerResolver($container),
        ];
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ParameterResolver[]
     */
    private function generators(ContainerInterface $container)
    {
        $generators = [

            AssociativeArrayResolver::class       => function (
                ReflectionParameter $parameter,
                array $provided
            ) {
                if (array_key_exists($parameter->name, $provided)) {
                    yield $provided[$parameter->name];
                }
            },

            NumericArrayResolver::class           => function (
                ReflectionParameter $parameter,
                array $provided
            ) {
                if (array_key_exists($parameter->getPosition(), $provided)) {
                    yield $provided[$parameter->getPosition()];
                }
            },

            TypeHintResolver::class               => function (
                ReflectionParameter $parameter,
                array $provided
            ) {
                if (($class = $parameter->getClass()) && array_key_exists($class->name, $provided)) {
                    yield $provided[$class->name];
                }
            },

            DefaultValueResolver::class           => function (
                ReflectionParameter $parameter
            ) {
                if ($parameter->isOptional()) {
                    try {
                        yield $parameter->getDefaultValue();
                    } catch (\ReflectionException $e) {
                        // Can't get default values from PHP internal classes and functions
                    }
                }
            },

            TypeHintContainerResolver::class      => function (
                ReflectionParameter $parameter
            ) use ($container) {
                if (($class = $parameter->getClass()) && $container->has($class->name)) {
                    yield $container->get($class->name);
                }
            },

            ParameterNameContainerResolver::class => function (
                ReflectionParameter $parameter
            ) use ($container) {
                if (($name = $parameter->name) && $container->has($name)) {
                    yield $container->get($name);
                }
            },

        ];

        return array_map(function(callable $generator) {
            return new GeneratorResolver($generator);
        }, $generators);
    }

    /**
     * @covers ::getParameters
     */
    public function testGetParametersWithDocBlockTags()
    {
        $actual = (new GeneratorResolver(function(ReflectionParameter $parameter, array $provided, Param $tag = null) {
            yield [$parameter->getName() => $tag ? [
                'type' => (string)$tag->getType(),
                'desc' => (string)$tag->getDescription(),
            ] : null];
        }))->getParameters(new ReflectionFunction(
            /**
             * @param string $a1
             * @param bool $a3
             * @param mixed ...$a4 any description
             */
            function($a1, $a2, $a3 = 'default', ...$a4) {}
        ), [], []);

        $this->assertEquals([
            ['a1' => ['type' => 'string', 'desc' => '']],
            ['a2' => null],
            ['a3' => ['type' => 'bool', 'desc' => '']],
            ['a4' => ['type' => 'mixed', 'desc' => 'any description']],
        ], $actual);
    }
}
