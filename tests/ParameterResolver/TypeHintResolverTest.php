<?php declare(strict_types=1);

namespace Invoker\Test\ParameterResolver;

use Invoker\ParameterResolver\TypeHintResolver;
use PHPUnit\Framework\TestCase;

class TypeHintResolverTest extends TestCase
{
    private const FIXTURE = TypeHintResolverFixture::class;

    /** @var TypeHintResolver */
    private $resolver;

    public function setUp(): void
    {
        $this->resolver = new TypeHintResolver;
    }

    /**
     * @test
     */
    public function should_resolve_parameter_with_typehint()
    {
        $callable = function (TypeHintResolverFixture $foo) {
        };
        $reflection = new \ReflectionFunction($callable);

        $fixture = new TypeHintResolverFixture;

        $parameters = $this->resolver->getParameters($reflection, [self::FIXTURE => $fixture], []);

        $this->assertCount(1, $parameters);
        $this->assertSame($fixture, $parameters[0]);
    }

    /**
     * @test
     */
    public function should_skip_parameter_if_provided_parameters_do_not_contain_typehint()
    {
        $callable = function (TypeHintResolverFixture $foo) {
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
        $callable = function (TypeHintResolverFixture $foo) {
        };
        $reflection = new \ReflectionFunction($callable);

        $fixture = new TypeHintResolverFixture;

        $resolvedParameters = ['first param value'];
        $parameters = $this->resolver->getParameters($reflection, [self::FIXTURE => $fixture], $resolvedParameters);

        $this->assertSame($resolvedParameters, $parameters);
    }
}

class TypeHintResolverFixture
{
}
