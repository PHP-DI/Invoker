<?php

namespace Invoker\Test\ParameterResolver;

use Invoker\ParameterResolver\TypeHintResolver;
use PHPUnit\Framework\TestCase;

class TypeHintResolverTest extends TestCase
{
    const FIXTURE = 'Invoker\Test\ParameterResolver\TypeHintResolverFixture';

    /**
     * @var TypeHintResolver
     */
    private $resolver;

    public function setUp()
    {
        $this->resolver = new TypeHintResolver;
    }

    /**
     * @test
     */
    public function should_resolve_parameter_with_typehint()
    {
        $callable = function (TypeHintResolverFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $fixture = new TypeHintResolverFixture;

        $parameters = $this->resolver->getParameters($reflection, array(self::FIXTURE => $fixture), array());

        $this->assertCount(1, $parameters);
        $this->assertSame($fixture, $parameters[0]);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_provided_parameters_do_not_contain_typehint()
    {
        $callable = function (TypeHintResolverFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $parameters = $this->resolver->getParameters($reflection, array(), array());

        $this->assertCount(0, $parameters);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_already_resolved()
    {
        $callable = function (TypeHintResolverFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $fixture = new TypeHintResolverFixture;

        $resolvedParameters = array('first param value');
        $parameters = $this->resolver->getParameters($reflection, array(self::FIXTURE => $fixture), $resolvedParameters);

        $this->assertSame($resolvedParameters, $parameters);
    }
}

class TypeHintResolverFixture
{
}
