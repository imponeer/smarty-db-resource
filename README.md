[![License](https://img.shields.io/github/license/imponeer/smarty-db-resource.svg)](LICENSE)
[![GitHub release](https://img.shields.io/github/release/imponeer/smarty-db-resource.svg)](https://github.com/imponeer/smarty-db-resource/releases) [![Maintainability](https://api.codeclimate.com/v1/badges/c284ca86c6df6e98b9d0/maintainability)](https://codeclimate.com/github/imponeer/smarty-db-resource/maintainability) [![PHP](https://img.shields.io/packagist/php-v/imponeer/smarty-db-resource.svg)](http://php.net) 
[![Packagist](https://img.shields.io/packagist/dm/imponeer/smarty-db-resource.svg)](https://packagist.org/packages/imponeer/smarty-db-resource)

# Smarty DB resource

[Smarty](https://smarty.net) resource plugin that can read templates from database.

This plugin is inspired but similar from [Xoops](https://xoops.org) - [resource.db](https://github.com/XOOPS/XoopsCore25/blob/v2.5.8/htdocs/class/smarty/xoops_plugins/resource.db.php).

## Installation

To install and use this package, we recommend to use [Composer](https://getcomposer.org):

```bash
composer require imponeer/smarty-db-resource
```

Otherwise, you need to include manually files from `src/` directory. 

## Registering in Smarty

If you want to use these extensions from this package in your project you need register them with [`registerResource` function](https://www.smarty.net/docs/en/api.register.resource.tpl) from [Smarty](https://www.smarty.net). For example:
```php
$smarty = new \Smarty();
$plugin = new \Imponeer\Smarty\Extensions\DBResource\DBResource(
    $cacheItemPool, // psr-6 compatible cache instance
    $pdo, // PDO compatible database connection
    'default', // current template set name
    'tplfile',
    'tpl_source,
    'tpl_lastmodified',
    'tpl_tplset',
    'tpl_file',
    function (array $row): string { // function that converts database row info into string of real file
       return $row['file'];
    },
    'default'
);
$smarty->registerResource($plugin->getName(), $plugin);
```

## Using from templates

To use this resource from templates, you need to use `db:` prefix when accessing files. For example :
```smarty
  {include file="db:/images/image.tpl"}
```

## How to contribute?

If you want to add some functionality or fix bugs, you can fork, change and create pull request. If you not sure how this works, try [interactive GitHub tutorial](https://try.github.io).

If you found any bug or have some questions, use [issues tab](https://github.com/imponeer/smarty-db-resource/issues) and write there your questions.