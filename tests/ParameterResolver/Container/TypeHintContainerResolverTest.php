<?php

namespace Invoker\Test\ParameterResolver\Container;

use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\Test\Mock\ArrayContainer;
use PHPUnit\Framework\TestCase;

class TypeHintContainerResolverTest extends TestCase
{
    const FIXTURE = 'Invoker\Test\ParameterResolver\Container\TypeHintContainerResolverFixture';

    /**
     * @var TypeHintContainerResolver
     */
    private $resolver;

    /**
     * @var ArrayContainer
     */
    private $container;

    public function setUp()
    {
        $this->container = new ArrayContainer;
        $this->resolver = new TypeHintContainerResolver($this->container);
    }

    /**
     * @test
     */
    public function should_resolve_parameter_with_typehint_and_container()
    {
        $callable = function (TypeHintContainerResolverFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $fixture = new TypeHintContainerResolverFixture;
        $this->container->set(self::FIXTURE, $fixture);

        $parameters = $this->resolver->getParameters($reflection, array(), array());

        $this->assertCount(1, $parameters);
        $this->assertSame($fixture, $parameters[0]);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_container_cannot_provide_typehint()
    {
        $callable = function (TypeHintContainerResolverFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $parameters = $this->resolver->getParameters($reflection, array(), array());

        $this->assertCount(0, $parameters);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_already_resolved()
    {
        $callable = function (TypeHintContainerResolverFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $this->container->set(self::FIXTURE, new TypeHintContainerResolverFixture);

        $resolvedParameters = array('first param value');
        $parameters = $this->resolver->getParameters($reflection, array(), $resolvedParameters);

        $this->assertSame($resolvedParameters, $parameters);
    }
}

class TypeHintContainerResolverFixture
{
}
