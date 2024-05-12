# phputil/flags-pdo

[![Version](https://poser.pugx.org/phputil/flags-pdo/v?style=flat-square)](https://packagist.org/packages/phputil/flags-pdo)
![Build](https://github.com/thiagodp/phputil-flags-pdo/actions/workflows/ci.yml/badge.svg?style=flat)
[![License](https://poser.pugx.org/phputil/flags-pdo/license?style=flat-square)](https://packagist.org/packages/phputil/flags-pdo)
[![PHP](http://poser.pugx.org/phputil/flags-pdo/require/php)](https://packagist.org/packages/phputil/flags-pdo)

> A PDO-based storage for the [phputil/flags](https://github.com/thiagodp/phputil-flags) feature flags framework

Currently supported drivers:
- sqlite
- mysql

## Installation

```bash
composer require phputil/flags-pdo
```

## Usage

```php
require_once 'vendor/autoload.php';

use phputil\flags\pdo\PDOBasedStorage;

$pdo = /* create you PDO instance here, e.g.: new PDO( 'sqlite:example.sqlite' ) */;
$storage = new PDOBasedStorage( $pdo );

// Now use it with phputil\flags
$flags = new phputil\flags\FlagManager( $storage );
...
```

## License

[MIT](/LICENSE) © [Thiago Delgado Pinto](https://github.com/thiagodp)
