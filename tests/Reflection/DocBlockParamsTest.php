<?php

namespace Invoker\Reflection;

use Generator;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\TypeResolver;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * @coversDefaultClass DocBlockParams
 */
class DocBlockParamsTest extends TestCase
{
    /**
     * @return array
     */
    public function providerTestInvoke()
    {
        $resolver = new TypeResolver;
        $fn =
            /**
             * @author foo
             * @author bar
             *
             * @param int $p1
             * @param bool $p2
             */
            function() {};

        return [
            'empty' => [[], function(){}],
            'default' => [
                $params = [
                    'p1' => new Param('p1', $resolver->resolve('int'), false, new Description('')),
                    'p2' => new Param('p2', $resolver->resolve('bool'), false, new Description('')),
                ],
                $fn
            ],
            'without index' => [array_values($params), $fn, ['param', null]],
            'author' => [
                [new Author('foo', ''), new Author('bar', '')],
                $fn,
                ['author', null]
            ],
        ];
    }

    /**
     * @covers ::__invoke
     * @dataProvider  providerTestInvoke
     *
     * @param $expected
     * @param $callable
     * @param array $args
     */
    public function testInvoke($expected, $callable, array $args = [])
    {
        $params = (new DocBlockParams(new ReflectionFunction($callable)));
        $this->assertInstanceOf(Generator::class, $generator = $params(...$args));
        $this->assertEquals($expected, iterator_to_array($generator));
    }
}
