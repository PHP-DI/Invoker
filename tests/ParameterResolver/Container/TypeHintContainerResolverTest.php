<?php declare(strict_types=1);

namespace Invoker\Test\ParameterResolver\Container;

use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\Test\Mock\ArrayContainer;
use PHPUnit\Framework\TestCase;

class TypeHintContainerResolverTest extends TestCase
{
    private const FIXTURE = TypeHintContainerResolverFixture::class;

    /** @var TypeHintContainerResolver */
    private $resolver;

    /** @var ArrayContainer */
    private $container;

    public function setUp(): void
    {
        $this->container = new ArrayContainer;
        $this->resolver = new TypeHintContainerResolver($this->container);
    }

    /**
     * @test
     */
    public function should_resolve_parameter_with_typehint_and_container()
    {
        $callable = function (TypeHintContainerResolverFixture $foo, self $bar) {
        };
        $reflection = new \ReflectionFunction($callable);

        $fixture = new TypeHintContainerResolverFixture;
        $this->container->set(self::FIXTURE, $fixture);
        $this->container->set(self::class, $this);

        $parameters = $this->resolver->getParameters($reflection, [], []);

        $this->assertCount(2, $parameters);
        $this->assertSame($fixture, $parameters[0]);
        $this->assertSame($this, $parameters[1]);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_container_cannot_provide_typehint()
    {
        $callable = function (TypeHintContainerResolverFixture $foo) {
        };
        $reflection = new \ReflectionFunction($callable);

        $parameters = $this->resolver->getParameters($reflection, [], []);

        $this->assertCount(0, $parameters);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_already_resolved()
    {
        $callable = function (TypeHintContainerResolverFixture $foo) {
        };
        $reflection = new \ReflectionFunction($callable);

        $this->container->set(self::FIXTURE, new TypeHintContainerResolverFixture);

        $resolvedParameters = ['first param value'];
        $parameters = $this->resolver->getParameters($reflection, [], $resolvedParameters);

        $this->assertSame($resolvedParameters, $parameters);
    }
}

class TypeHintContainerResolverFixture
{
}
