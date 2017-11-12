<?php

namespace Invoker\Test\ParameterResolver\Container;

use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\Test\Mock\ArrayContainer;
use PHPUnit\Framework\TestCase;

class ParameterNameContainerResolverTest extends TestCase
{
    /**
     * @var ParameterNameContainerResolver
     */
    private $resolver;

    /**
     * @var ArrayContainer
     */
    private $container;

    public function setUp()
    {
        $this->container = new ArrayContainer;
        $this->resolver = new ParameterNameContainerResolver($this->container);
    }

    /**
     * @test
     */
    public function should_resolve_parameter_with_parameter_name_from_container()
    {
        $callable = function ($foo) {};
        $reflection = new \ReflectionFunction($callable);

        $this->container->set('foo', 'bar');

        $parameters = $this->resolver->getParameters($reflection, array(), array());

        $this->assertCount(1, $parameters);
        $this->assertSame('bar', $parameters[0]);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_container_cannot_provide_parameter()
    {
        $callable = function ($foo) {};
        $reflection = new \ReflectionFunction($callable);

        $parameters = $this->resolver->getParameters($reflection, array(), array());

        $this->assertCount(0, $parameters);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_already_resolved()
    {
        $callable = function ($foo) {};
        $reflection = new \ReflectionFunction($callable);

        $this->container->set('foo', 'bar');

        $resolvedParameters = array('first param value');
        $parameters = $this->resolver->getParameters($reflection, array(), $resolvedParameters);

        $this->assertSame($resolvedParameters, $parameters);
    }
}
