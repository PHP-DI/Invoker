<?php

namespace Invoker\Exception;

/**
 * The given callable is not actually callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NotCallableException extends InvocationException
{
    /**
     * @param string $value
     * @return self
     */
    public static function fromInvalidCallable($value)
    {
        if (is_object($value)) {
            $message = sprintf('Instance of %s is not a callable', get_class($value));
        } elseif (is_array($value) && isset($value[0]) && isset($value[1]) && is_object($value[0])) {
            $message = sprintf('%s::%s() is not a callable', get_class($value[0]), $value[1]);
        } else {
            $message = var_export($value, true) . ' is neither a callable nor a valid container entry';
        }

        return new self($message);
    }
}
