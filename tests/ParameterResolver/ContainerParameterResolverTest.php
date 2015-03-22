<?php

namespace Invoker\Test\ParameterResolver;

use Invoker\ParameterResolver\ContainerParameterResolver;
use Invoker\Test\Mock\ArrayContainer;

class ContainerParameterResolverTest extends \PHPUnit_Framework_TestCase
{
    const FIXTURE = 'Invoker\Test\ParameterResolver\ContainerParameterResolverTestFixture';

    /**
     * @var ContainerParameterResolver
     */
    private $resolver;

    /**
     * @var ArrayContainer
     */
    private $container;

    public function setUp()
    {
        $this->container = new ArrayContainer;
        $this->resolver = new ContainerParameterResolver($this->container);
    }

    /**
     * @test
     */
    public function should_resolve_parameter_with_typehint_and_container()
    {
        $callable = function (ContainerParameterResolverTestFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $fixture = new ContainerParameterResolverTestFixture;
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
        $callable = function (ContainerParameterResolverTestFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $parameters = $this->resolver->getParameters($reflection, array(), array());

        $this->assertCount(0, $parameters);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_already_resolved()
    {
        $callable = function (ContainerParameterResolverTestFixture $foo) {};
        $reflection = new \ReflectionFunction($callable);

        $this->container->set(self::FIXTURE, new ContainerParameterResolverTestFixture);

        $resolvedParameters = array('first param value');
        $parameters = $this->resolver->getParameters($reflection, array(), $resolvedParameters);

        $this->assertSame($resolvedParameters, $parameters);
    }
}

class ContainerParameterResolverTestFixture
{
}
