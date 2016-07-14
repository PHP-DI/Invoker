<?php

namespace Invoker\Test\Mock;

use Interop\Container\ContainerInterface;

/**
 * Simple container.
 */
class ArrayContainer implements ContainerInterface
{
    private $entries = array();

    public function __construct(array $entries = array())
    {
        $this->entries = $entries;
    }

    public function get($id)
    {
        if (!array_key_exists($id, $this->entries)) {
            throw new NotFound;
        }

        return $this->entries[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->entries);
    }

    public function set($id, $value)
    {
        $this->entries[$id] = $value;
    }
}
