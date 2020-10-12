<?php declare(strict_types=1);

namespace Invoker\Test\Mock;

use Psr\Container\NotFoundExceptionInterface;

class NotFound extends \Exception implements NotFoundExceptionInterface
{
}
