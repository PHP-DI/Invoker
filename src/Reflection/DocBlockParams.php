<?php

namespace Invoker\Reflection;

use Generator;
use IteratorAggregate;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionFunctionAbstract;

/**
 * Extract {@param} tags from the function reflection.
 * Depends on phpdocumentor/reflection-docblock package.
 */
class DocBlockParams implements IteratorAggregate
{
    /**
     * @var ReflectionFunctionAbstract
     */
    private $reflection;

    /**
     * @param ReflectionFunctionAbstract $reflection
     */
    public function __construct(ReflectionFunctionAbstract $reflection)
    {
        $this->reflection = $reflection;
    }

    /**
     * @param string $tagName
     * @param string $indexBy
     *
     * @return Generator|DocBlock\Tag[]
     */
    public function __invoke($tagName = 'param', $indexBy = 'getVariableName')
    {
        if (class_exists(DocBlockFactory::class) && $comment = $this->reflection->getDocComment()) {
            $doc = DocBlockFactory::createInstance()->create($comment);
            foreach ($doc->getTagsByName($tagName) as $param) {
                if ($indexBy) {
                    yield $param->$indexBy() => $param;
                } else {
                    yield $param;
                }
            }
        }
    }

    /**
     * @return Generator|DocBlock\Tags\Param[]
     */
    public function getIterator()
    {
        return $this();
    }
}
