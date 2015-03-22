<?php

namespace Invoker\ParameterResolver;

use ReflectionFunctionAbstract;

/**
 * Dispatches the call to other resolvers until all parameters are resolved.
 *
 * Chain of responsibility pattern.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ParameterResolverChain implements ParameterResolver
{
    /**
     * @var ParameterResolver[]
     */
    private $resolvers = array();

    public function __construct(array $resolvers = array())
    {
        $this->resolvers = $resolvers;
    }

    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ) {
        $parameterCount = $reflection->getNumberOfRequiredParameters();

        foreach ($this->resolvers as $resolver) {
            // TODO optimize: stop traversing once all parameters are resolved
            $resolvedParameters = $resolver->getParameters(
                $reflection,
                $providedParameters,
                $resolvedParameters
            );
        }

        $this->assertMandatoryParametersAreResolved($parameterCount, $resolvedParameters, $reflection);

        return $resolvedParameters;
    }

    /**
     * Push a parameter resolver after the ones already registered.
     *
     * @param ParameterResolver $resolver
     */
    public function pushResolver(ParameterResolver $resolver)
    {
        $this->resolvers[] = $resolver;
    }

    /**
     * Insert a parameter resolver before the ones already registered.
     *
     * @param ParameterResolver $resolver
     */
    public function unshiftResolver(ParameterResolver $resolver)
    {
        array_unshift($this->resolvers, $resolver);
    }

    private function assertMandatoryParametersAreResolved(
        $parameterCount,
        $parameters,
        ReflectionFunctionAbstract $reflection
    ) {
        // TODO is there a more efficient way?
        for ($i = 0; $i < $parameterCount; $i++) {
            if (! array_key_exists($i, $parameters)) {
                $reflectionParameters = $reflection->getParameters();
                $parameter = $reflectionParameters[$i];

                throw new \RuntimeException(sprintf(
                    'Unable to invoke the callable because no value was given for parameter %d ($%s)',
                    $i + 1,
                    $parameter->getName()
                ));
            }
        }
    }
}
