<?php declare(strict_types=1);

namespace Invoker\Test\Mock;

use Psr\Container\ContainerInterface;

/**
 * Simple container.
 */
class ArrayContainer implements ContainerInterface
{
    private $entries;

    public function __construct(array $entries = [])
    {
        $this->entries = $entries;
    }

    /** {@inheritDoc} */
    public function get($id)
    {
        if (! array_key_exists($id, $this->entries)) {
            throw new NotFound;
        }

        return $this->entries[$id];
    }

    /** {@inheritDoc} */
    public function has($id)
    {
        return array_key_exists($id, $this->entries);
    }

    /**
     * @param mixed $value
     */
    public function set(string $id, $value)
    {
        $this->entries[$id] = $value;
    }
}
