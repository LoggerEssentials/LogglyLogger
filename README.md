logger/loggly
=============

A psr-3 compatible loggly implementation.

## Installation

`composer require logger/loggly`

## Usage

```php
$token = '<LogglyToken>';
$tags = ['tag1', 'tag2', 'tag3'];
$logger = new Logger\LogglyLogger($token, $tags);

$logger->info('Hello World');
```