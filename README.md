# Invoker

Generic and extensible callable invoker.

[![Build Status](https://img.shields.io/travis/mnapoli/invoker.svg?style=flat-square)](https://travis-ci.org/mnapoli/invoker)
[![Coverage Status](https://img.shields.io/coveralls/mnapoli/invoker/master.svg?style=flat-square)](https://coveralls.io/r/mnapoli/invoker?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/mnapoli/invoker.svg?style=flat-square)](https://scrutinizer-ci.com/g/mnapoli/invoker/?branch=master)
[![Latest Version](https://img.shields.io/github/release/mnapoli/invoker.svg?style=flat-square)](https://packagist.org/packages/mnapoli/invoker)
[![Total Downloads](https://img.shields.io/packagist/dt/mnapoli/invoker.svg?style=flat-square)](https://packagist.org/packages/mnapoli/invoker)

## Why?

Who doesn't need an over-engineered `call_user_func()`?

### Named parameters

Does this [Silex](http://silex.sensiolabs.org) example look familiar:

```php
$app->get('/project/{project}/issue/{issue}', function ($project, $issue) {
    // ...
});
```

Or this command defined with [Silly](https://github.com/mnapoli/silly#usage):

```php
$app->command('greet [name] [--yell]', function ($name, $yell) {
    // ...
});
```

Same pattern in [Slim](http://www.slimframework.com):

```php
$app->get('/hello/:name', function ($name) {
    // ...
});
```

You get the point. These frameworks invoke the controller/command/handler using something akin to named parameters: whatever the order of the parameters, they are matched by their name.

**This library allows to invoke callables with named parameters in a generic and extensible way.**

### Dependency injection

Anyone familiar with AngularJS is familiar with how dependency injection is performed:

```js
angular.controller('MyController', ['dep1', 'dep2', function(dep1, dep2) {
    // ...
}]);
```

In PHP we find this pattern again in some frameworks and DI containers with partial to full support. For example in Silex you can type-hint the application to get it injected, but it only works with `Silex\Application`:

```php
$app->get('/hello/{name}', function (Silex\Application $app, $name) {
    // ...
});
```

In Silly, it only works with `OutputInterface` to inject the application output:

```php
$app->command('greet [name]', function ($name, OutputInterface $output) {
    // ...
});
```

[PHP-DI](http://php-di.org/doc/container.html) provides a way to invoke a callable and resolve all dependencies from the container using type-hints:

```php
$container->call(function (Logger $logger, EntityManager $em) {
    // ...
});
```

**This library provides clear extension points to let frameworks implement any kind of dependency injection support they want.**

### TL/DR

In short, this library is meant to be a base building block for calling a function with named parameters and/or dependency injection.

## Installation

```sh
$ composer require mnapoli/invoker
```

## Usage

### Default behavior

By default the `Invoker` can call using named parameters:

```php
$invoker = new Invoker\Invoker;

$invoker->call(function () {
    echo 'Hello world!';
});

// Simple parameter array
$invoker->call(function ($name) {
    echo 'Hello ' . $name;
}, ['John']);

// Named parameters
$invoker->call(function ($name) {
    echo 'Hello ' . $name;
}, [
    'name' => 'John'
]);

// Use the default value
$invoker->call(function ($name = 'world') {
    echo 'Hello ' . $name;
});

// Invoke any PHP callable
$invoker->call(['MyClass', 'myStaticMethod']);
```

### Parameter resolvers

Extending the behavior of the `Invoker` is easy and is done by implementing a `ParameterResolver`:

```php
interface ParameterResolver
{
    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    );
}
```

- `$providedParameters` contains the parameters provided by the user when calling `$invoker->call($callable, $parameters)`
- `$resolvedParameters` contains parameters that have already been resolved by other parameter resolvers

An `Invoker` can chain multiple parameter resolvers to mix behaviors, e.g. you can mix "named parameters" support with "dependency injection" support. This is why a `ParameterResolver` should skip parameters that are already resolved in `$resolvedParameters`.

Here is an implementation example for dumb dependency injection that creates a new instance of the classes type-hinted:

```php
class MyParameterResolver implements ParameterResolver
{
    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ) {
        foreach ($reflection->getParameters() as $index => $parameter) {
            if (array_key_exists($index, $resolvedParameters)) {
                // Skip already resolved parameters
                continue;
            }

            $class = $parameter->getClass();

            if ($class) {
                $resolvedParameters[$index] = $class->newInstance();
            }
        }

        return $resolvedParameters;
    }
}
```

To use it:

```php
$invoker = new Invoker\Invoker(new MyParameterResolver);

$invoker->call(function (ArticleManager $articleManager) {
    $articleManager->publishArticle('Hello world', 'This is the article content.');
});
```
