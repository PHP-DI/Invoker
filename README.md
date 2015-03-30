# Invoker

Generic and extensible callable invoker.

[![Build Status](https://img.shields.io/travis/mnapoli/Invoker.svg?style=flat-square)](https://travis-ci.org/mnapoli/Invoker)
[![Coverage Status](https://img.shields.io/coveralls/mnapoli/Invoker/master.svg?style=flat-square)](https://coveralls.io/r/mnapoli/Invoker?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/mnapoli/Invoker.svg?style=flat-square)](https://scrutinizer-ci.com/g/mnapoli/Invoker/?branch=master)
[![Latest Version](https://img.shields.io/github/release/mnapoli/invoker.svg?style=flat-square)](https://packagist.org/packages/mnapoli/invoker)

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

Dependency injection in parameters is supported but needs to be configured with your container. Read on or jump to [*Built-in support for dependency injection*](#built-in-support-for-dependency-injection) if you are impatient.

Additionally, callables can also be resolved from your container. Read on or jump to [*Resolving callables from a container*](#resolving-callables-from-a-container) if you are impatient.

### Parameter resolvers

Extending the behavior of the `Invoker` is easy and is done by implementing a [`ParameterResolver`](https://github.com/mnapoli/Invoker/blob/master/src/ParameterResolver/ParameterResolver.php).

This is explained in details the [Parameter resolvers documentation](doc/parameter-resolvers.md).

#### Built-in support for dependency injection

Rather than have you re-implement support for dependency injection with different containers every time, this package ships with a [`TypeHintContainerResolver`](https://github.com/mnapoli/Invoker/blob/master/src/ParameterResolver/Container/TypeHintContainerResolver.php) that can work with any dependency injection container thanks to [container-interop](https://github.com/container-interop/container-interop).

Using it is simple:

```php
// $container must be an instance of Interop\Container\ContainerInterface
$container = ...

$containerResolver = new TypeHintContainerResolver($container);

$invoker = new Invoker\Invoker;
// Register it before all the other parameter resolvers
$invoker->getParameterResolver()->unshiftResolver($containerResolver);
```

This parameter resolver will use the type-hints to look into the container:

```php
$invoker->call(function (Psr\Logger\LoggerInterface $logger) {
    // ...
});
```

In this example it will `->get('Psr\Logger\LoggerInterface')` from the container.

*Note:* if you container is not compliant with [container-interop](https://github.com/container-interop/container-interop), you can use the [Acclimate](https://github.com/jeremeamia/acclimate-container) package.

This implementation will only do dependency injection based on type-hints. Implementing support for doing dependency injection based on parameter names, or whatever, is easy and up to you!

### Resolving callables from a container

The `Invoker` can be wired to your DI container to resolve the callables.

For example with an invokable class:

```php
class MyHandler
{
    public function __invoke()
    {
        // ...
    }
}

// By default this doesn't work: an instance of the class should be provided
$invoker->call('MyHandler');

// If we set up the container to use
$invoker = new Invoker\Invoker(null, $container);
// Now 'MyHandler' is resolved using the container!
$invoker->call('MyHandler');
```

The same works for a class method:

```php
class WelcomeController
{
    public function home()
    {
        // ...
    }
}

// By default this doesn't work: home() is not a static method
$invoker->call(['WelcomeController', 'home']);

// If we set up the container to use
$invoker = new Invoker\Invoker(null, $container);
// Now 'WelcomeController' is resolved using the container!
$invoker->call(['WelcomeController', 'home']);
```

That feature can be used as the base building block for a framework's dispatcher.

Again, any [container-interop](https://github.com/container-interop/container-interop) compliant container can be provided, and [Acclimate](https://github.com/jeremeamia/acclimate-container) can be used for incompatible containers.
