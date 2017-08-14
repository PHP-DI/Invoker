<?php

namespace Invoker\Test\Mock;

use Psr\Container\NotFoundExceptionInterface;

class NotFound extends \Exception implements NotFoundExceptionInterface
{
}
