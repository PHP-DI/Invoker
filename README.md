# Invoker

Generic and extensible callable invoker.

[![Build Status](https://img.shields.io/travis/mnapoli/invoker.svg?style=flat-square)](https://travis-ci.org/mnapoli/invoker)
[![Coverage Status](https://img.shields.io/coveralls/mnapoli/invoker/master.svg?style=flat-square)](https://coveralls.io/r/mnapoli/invoker?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/mnapoli/invoker.svg?style=flat-square)](https://scrutinizer-ci.com/g/mnapoli/invoker/?branch=master)
[![Latest Version](https://img.shields.io/github/release/mnapoli/invoker.svg?style=flat-square)](https://packagist.org/packages/mnapoli/invoker)
[![Total Downloads](https://img.shields.io/packagist/dt/mnapoli/invoker.svg?style=flat-square)](https://packagist.org/packages/mnapoli/invoker)

## Why?

TODO

## Installation

```sh
$ composer require mnapoli/invoker
```

## Usage

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
