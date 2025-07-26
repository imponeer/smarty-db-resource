[![License](https://img.shields.io/github/license/imponeer/smarty-db-resource.svg)](LICENSE) [![GitHub release](https://img.shields.io/github/release/imponeer/smarty-db-resource.svg)](https://github.com/imponeer/smarty-db-resource/releases) [![PHP](https://img.shields.io/packagist/php-v/imponeer/smarty-db-resource.svg)](http://php.net) [![Packagist](https://img.shields.io/packagist/dm/imponeer/smarty-db-resource.svg)](https://packagist.org/packages/imponeer/smarty-db-resource) [![Smarty version requirement](https://img.shields.io/packagist/dependency-v/imponeer/smarty-db-resource/smarty%2Fsmarty)](https://smarty-php.github.io)

# Smarty DB Resource

> Database-driven template resource for Smarty

[Smarty](https://smarty.net) resource plugin that enables reading templates directly from a database. This powerful extension allows you to store and manage your Smarty templates in a database instead of the filesystem, providing dynamic template management capabilities.

This plugin is inspired by and similar to [Xoops](https://xoops.org) - [resource.db](https://github.com/XOOPS/XoopsCore25/blob/v2.5.8/htdocs/class/smarty/xoops_plugins/resource.db.php).

## Installation

To install and use this package, we recommend to use [Composer](https://getcomposer.org):

```bash
composer require imponeer/smarty-db-resource
```

Otherwise, you need to include manually files from `src/` directory.



## Database Structure

This plugin requires a specific database table structure to store template information. The table should contain the following columns:

### Required Columns

| Column Name | Type | Description |
|-------------|------|-------------|
| Template ID | `MEDIUMINT UNSIGNED AUTO_INCREMENT` | Primary key for the template record |
| Template Set | `VARCHAR(50)` | Template set identifier (e.g., 'default', 'theme1') |
| Template File | `VARCHAR(50)` | Template filename (e.g., 'header.tpl', 'footer.tpl') |
| Template Source | `TEXT` | The actual template source code (optional if using file-based templates) |
| Last Modified | `INT UNSIGNED` | Unix timestamp of last modification |
| Template Description | `VARCHAR(255)` | Human-readable description of the template |
| Last Imported | `INT UNSIGNED` | Unix timestamp of last import |
| Template Type | `VARCHAR(20)` | Template type identifier |

### Example Table Schema

```sql
CREATE TABLE `tplfile` (
    `tpl_id` MEDIUMINT UNSIGNED AUTO_INCREMENT,
    `tpl_refid` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    `tpl_tplset` VARCHAR(50) NOT NULL DEFAULT 'default',
    `tpl_file` VARCHAR(50) NOT NULL DEFAULT '',
    `tpl_desc` VARCHAR(255) NOT NULL DEFAULT '',
    `tpl_lastmodified` INT UNSIGNED NOT NULL DEFAULT '0',
    `tpl_lastimported` INT UNSIGNED NOT NULL DEFAULT '0',
    `tpl_type` VARCHAR(20) NOT NULL DEFAULT '',
    `tpl_source` TEXT,
    PRIMARY KEY (`tpl_id`),
    KEY `tpl_tplset_file` (`tpl_tplset`, `tpl_file`)
);
```

**Note:** Column names are configurable when initializing the plugin, so you can adapt the plugin to your existing database schema.

## PDO Driver Support

This plugin supports multiple database systems through PDO drivers. The plugin automatically selects the appropriate driver based on your PDO connection:

- **SQLite**: Uses optimized SQLite-specific queries
- **All others**: Uses MySQL-compatible queries (works with most SQL databases)

## Setup

To register the database resource with Smarty, use the [`registerResource` function](https://www.smarty.net/docs/en/api.register.resource.tpl):

```php
use Imponeer\Smarty\Extensions\DatabaseResource\DatabaseResource;

// Create a Smarty instance
$smarty = new \Smarty\Smarty();

// Create PDO connection
$pdo = new PDO('mysql:host=localhost;dbname=your_database', $username, $password);

// Create and register the database resource
$plugin = new DatabaseResource(
    pdo: $pdo,                                    // PDO database connection
    tplSetName: 'default',                        // Current template set name
    templatesTableName: 'tplfile',                // Table name containing templates
    templateSourceColumnName: 'tpl_source',      // Column containing template source
    templateModificationColumnName: 'tpl_lastmodified', // Column with modification timestamp
    tplSetColumnName: 'tpl_tplset',              // Column identifying template set
    templateNameColumnName: 'tpl_file',          // Column containing template filename
    templatePathGetter: function (array $row): ?string { // Function to get file path from DB row
        return __DIR__ . '/templates/' . $row['tpl_file'];
    },
    defaultTplSetName: 'default'                  // Default template set fallback
);

$smarty->registerResource('db', $plugin);
```

### Custom Database Schema

You can adapt the plugin to your existing database schema by configuring the column names:

```php
$plugin = new DatabaseResource(
    pdo: $pdo,
    tplSetName: 'my_theme',
    templatesTableName: 'custom_templates',       // Your table name
    templateSourceColumnName: 'template_content', // Your source column
    templateModificationColumnName: 'modified_at', // Your timestamp column
    tplSetColumnName: 'theme_name',               // Your template set column
    templateNameColumnName: 'filename',          // Your filename column
    templatePathGetter: function (array $row): ?string {
        // Custom logic for file path resolution
        return '/path/to/templates/' . $row['filename'];
    },
    defaultTplSetName: 'default_theme'
);
```

### Template Path Resolution Examples

The `templatePathGetter` function allows you to customize how database records are converted to file paths:

```php
// Simple file path concatenation
$templatePathGetter = function (array $row): ?string {
    return __DIR__ . '/templates/' . $row['tpl_file'];
};

// Subdirectory organization by template type
$templatePathGetter = function (array $row): ?string {
    $subdir = $row['tpl_type'] ?? 'default';
    return __DIR__ . '/templates/' . $subdir . '/' . $row['tpl_file'];
};

// Conditional file resolution with validation
$templatePathGetter = function (array $row): ?string {
    if (empty($row['tpl_file'])) {
        return null; // No file path available
    }

    $basePath = __DIR__ . '/templates/';
    $filePath = $basePath . $row['tpl_file'];

    return is_file($filePath) ? $filePath : null;
};
```

### Using the Built-in TemplatePathResolver

The package includes a built-in `TemplatePathResolver` class that provides a clean, object-oriented alternative to closures for template path resolution:

```php
use Imponeer\Smarty\Extensions\DatabaseResource\Resolver\TemplatePathResolver;

// Create the resolver with your template base path
$templatePathResolver = new TemplatePathResolver('/path/to/templates');

// Use it in DatabaseResource
$plugin = new DatabaseResource(
    pdo: $pdo,
    tplSetName: 'default',
    templatesTableName: 'tplfile',
    templateSourceColumnName: 'tpl_source',
    templateModificationColumnName: 'tpl_lastmodified',
    tplSetColumnName: 'tpl_tplset',
    templateNameColumnName: 'tpl_file',
    templatePathGetter: $templatePathResolver,
    defaultTplSetName: 'default'
);
```

#### Custom Template File Column

You can also specify a custom column name for the template filename:

```php
// Use a custom column name for template files
$templatePathResolver = new TemplatePathResolver(
    templateBasePath: '/path/to/templates',
    templateFileColumn: 'custom_filename_column'
);
```

### Using with Symfony Container

To integrate with Symfony, you can leverage autowiring, which is the recommended approach for modern Symfony applications:

```yaml
# config/services.yaml
services:
    # Enable autowiring and autoconfiguration
    _defaults:
        autowire: true
        autoconfigure: true

    # Register your application's services
    App\:
        resource: '../src/*'
        exclude: '../src/{DependencyInjection,Entity,Tests,Kernel.php}'

    # Configure PDO connection
    PDO:
        arguments:
            $dsn: '%env(DATABASE_URL)%'
            $username: '%env(DB_USERNAME)%'
            $password: '%env(DB_PASSWORD)%'

    # Configure template path resolver
    Imponeer\Smarty\Extensions\DatabaseResource\Resolver\TemplatePathResolver:
        arguments:
            $templateBasePath: '%kernel.project_dir%/templates'

    # Configure DatabaseResource
    Imponeer\Smarty\Extensions\DatabaseResource\DatabaseResource:
        arguments:
            $pdo: '@PDO'
            $tplSetName: 'default'
            $templatesTableName: 'tplfile'
            $templateSourceColumnName: 'tpl_source'
            $templateModificationColumnName: 'tpl_lastmodified'
            $tplSetColumnName: 'tpl_tplset'
            $templateNameColumnName: 'tpl_file'
            $templatePathGetter: '@Imponeer\Smarty\Extensions\DatabaseResource\Resolver\TemplatePathResolver'
            $defaultTplSetName: 'default'

    # Configure Smarty with the extension
    Smarty\Smarty:
        calls:
            - [registerResource, ['db', '@Imponeer\Smarty\Extensions\DatabaseResource\DatabaseResource']]
```



Then in your application code:

```php
// Get the Smarty instance with the database resource already registered
$smarty = $container->get(\Smarty\Smarty::class);
```

### Using with PHP-DI

With PHP-DI container, you can take advantage of autowiring for a clean configuration:

```php
use function DI\create;
use function DI\get;
use function DI\factory;
use Imponeer\Smarty\Extensions\DatabaseResource\Resolver\TemplatePathResolver;

return [
    // Configure PDO
    PDO::class => factory(function () {
        return new PDO('mysql:host=localhost;dbname=your_database', $username, $password);
    }),

    // Configure TemplatePathResolver
    TemplatePathResolver::class => create()
        ->constructor(__DIR__ . '/templates'),

    // Configure DatabaseResource
    \Imponeer\Smarty\Extensions\DatabaseResource\DatabaseResource::class => create()
        ->constructor(
            get(PDO::class),
            'default',                    // tplSetName
            'tplfile',                   // templatesTableName
            'tpl_source',                // templateSourceColumnName
            'tpl_lastmodified',          // templateModificationColumnName
            'tpl_tplset',                // tplSetColumnName
            'tpl_file',                  // templateNameColumnName
            get(TemplatePathResolver::class), // templatePathGetter
            'default'                    // defaultTplSetName
        ),

    // Configure Smarty with the database resource
    \Smarty\Smarty::class => create()
        ->method('registerResource', 'db', get(\Imponeer\Smarty\Extensions\DatabaseResource\DatabaseResource::class))
];
```

Then in your application code:

```php
// Get the configured Smarty instance
$smarty = $container->get(\Smarty\Smarty::class);
```

### Using with League Container

If you're using League Container, you can register the extension like this:

```php
use League\Container\Container;
use Imponeer\Smarty\Extensions\DatabaseResource\DatabaseResource;
use Imponeer\Smarty\Extensions\DatabaseResource\Resolver\TemplatePathResolver;

// Create the container
$container = new Container();

// Register PDO
$container->add(PDO::class, function() {
    return new PDO('mysql:host=localhost;dbname=your_database', $username, $password);
});

// Register TemplatePathResolver
$container->add(TemplatePathResolver::class, function() {
    return new TemplatePathResolver(__DIR__ . '/templates');
});

// Register DatabaseResource
$container->add(DatabaseResource::class, function() use ($container) {
    return new DatabaseResource(
        pdo: $container->get(PDO::class),
        tplSetName: 'default',
        templatesTableName: 'tplfile',
        templateSourceColumnName: 'tpl_source',
        templateModificationColumnName: 'tpl_lastmodified',
        tplSetColumnName: 'tpl_tplset',
        templateNameColumnName: 'tpl_file',
        templatePathGetter: $container->get(TemplatePathResolver::class),
        defaultTplSetName: 'default'
    );
});

// Register Smarty with the database resource
$container->add(\Smarty\Smarty::class, function() use ($container) {
    $smarty = new \Smarty\Smarty();
    // Configure Smarty...

    // Register the database resource
    $smarty->registerResource('db', $container->get(DatabaseResource::class));

    return $smarty;
});
```

Then in your application code:

```php
// Get the configured Smarty instance
$smarty = $container->get(\Smarty\Smarty::class);
```

## Usage

### Basic Template Inclusion

To use database-stored templates in your Smarty templates, use the `db:` prefix when referencing template files:

```smarty
{* Include a template from the database *}
{include file="db:header.tpl"}

{* Include with subdirectory structure *}
{include file="db:layouts/main.tpl"}

{* Include with variables *}
{include file="db:user/profile.tpl" user=$currentUser}
```

### Template Examples

#### Main Layout Template
```smarty
{* File: db:layout.tpl *}
<!DOCTYPE html>
<html>
<head>
    <title>{$pageTitle|default:"My Website"}</title>
    {include file="db:includes/head.tpl"}
</head>
<body>
    {include file="db:includes/header.tpl"}

    <main>
        {$content}
    </main>

    {include file="db:includes/footer.tpl"}
</body>
</html>
```

#### Dynamic Content Loading
```smarty
{* Load different templates based on conditions *}
{if $userType == 'admin'}
    {include file="db:admin/dashboard.tpl"}
{else}
    {include file="db:user/dashboard.tpl"}
{/if}

{* Loop through template sections *}
{foreach $sections as $section}
    {include file="db:sections/{$section.template}" data=$section.data}
{/foreach}
```

### Template Set Management

The plugin supports multiple template sets, allowing you to have different themes or versions:

```php
// Switch to a different template set
$plugin = new DBResource(
    pdo: $pdo,
    tplSetName: 'mobile_theme',  // Use mobile-specific templates
    // ... other parameters
);
```

Templates are resolved with fallback logic:
1. First, look for templates in the specified template set
2. If not found, fall back to the default template set
3. If still not found, attempt to load from filesystem (if `templatePathGetter` is configured)

## Development

### Code Quality Tools

This project uses several tools to ensure code quality:

- **PHPUnit** - For unit testing
  ```bash
  composer test
  ```

- **PHP CodeSniffer** - For coding standards (PSR-12)
  ```bash
  composer phpcs    # Check code style
  composer phpcbf   # Fix code style issues automatically
  ```

- **PHPStan** - For static analysis
  ```bash
  composer phpstan
  ```

### Running Tests

The test suite includes comprehensive tests for database operations and template resolution:

```bash
# Run all tests
composer test

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Testing with Different Databases

The plugin includes tests for multiple database systems. You can test against different databases by configuring your test environment:

```php
// SQLite (default for tests)
$pdo = new PDO("sqlite::memory:");

// MySQL
$pdo = new PDO("mysql:host=localhost;dbname=test", $username, $password);

// PostgreSQL
$pdo = new PDO("pgsql:host=localhost;dbname=test", $username, $password);
```

## Documentation

API documentation is automatically generated and available in the project's wiki. For more detailed information about the classes and methods, please refer to the [project wiki](https://github.com/imponeer/smarty-db-resource/wiki).

## Contributing

Contributions are welcome! Here's how you can contribute:

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature-name`
3. **Commit your changes**: `git commit -am 'Add some feature'`
4. **Push to the branch**: `git push origin feature-name`
5. **Submit a pull request**

### Contribution Guidelines

- **Follow PSR-12 coding standards** - Use `composer phpcs` to check your code
- **Write tests** - Include unit tests for any new features or bug fixes
- **Update documentation** - Update README.md and inline documentation as needed
- **Test thoroughly** - Ensure your changes work with all supported database systems

### Reporting Issues

If you find a bug or have a feature request, please create an issue in the [issue tracker](https://github.com/imponeer/smarty-db-resource/issues).

When reporting bugs, please include:
- PHP version
- Database system and version
- Smarty version
- Steps to reproduce the issue
- Expected vs actual behavior

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Check code style: `composer phpcs`
5. Run static analysis: `composer phpstan`
