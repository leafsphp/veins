# Leaf Veins

Veins is a simple, lightweight, and fast templating engine for PHP. It is designed to be easy to use and easy to extend.

## Installation

You can install Veins using the Leaf CLI:

```bash
leaf install veins
```

Or with composer:

```bash
composer require leafs/veins
```

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Leaf\Veins;

$veins = new Veins();
$veins->configure([
    'templateDir' => __DIR__ . '/views/',
    'cacheDir' => __DIR__ . '/cache/',
]);
$veins->render('hello', ['name' => 'John']);
```

```html
<!-- views/hello.php -->
<h1>Hello, {$name}!</h1>
```

## Configuration

You can configure Veins by passing an array to the `configure` method:

```php
$veins->configure([
    'checksum' => [],
    'charset' => 'UTF-8',
    'debug' => false,
    'templateDir' => 'views/',
    'cacheDir' => 'cache/',
    'baseUrl' => '',
    'phpEnabled' => false,
    'autoEscape' => true,
    'sandbox' => true,
    'removeComments' => false,
    'customTags' => [],
]);
```

Find the full documentation at [leafphp.dev/modules/views/veins](https://leafphp.dev/modules/views/veins/).
