<?php
declare(strict_types = 1);

namespace Invoker\Test\Mock;

use Interop\Container\Exception\NotFoundException;

class NotFound extends \Exception implements NotFoundException
{
}
