# werx\config

[![Build Status](https://travis-ci.org/werx/config.png?branch=master)](https://travis-ci.org/werx/config) [![Total Downloads](https://poser.pugx.org/werx/config/downloads.png)](https://packagist.org/packages/werx/config) [![Latest Stable Version](https://poser.pugx.org/werx/config/v/stable.png)](https://packagist.org/packages/werx/config)

Use environment-specific configuration files in your app.

Features:

- Multiple Environment Support (example: local/test/prod)
- Extensible Configuration Providers
    - Includes an ArrayProvider and JsonProvider out of the box
    - Create your own providers by implementing `\werx\config\Providers\ProviderInterface`
- Load multiple config files with a single call

## Basic Usage

```php
# Get an instance of the ArrayProvider
$provider = new \werx\Config\Provider\ArrayProvider('/path/to/config/directory');

# Create a Config\Container Instance from this provider.
$config = new \werx\Config\Container($provider);

# Load config/config.php
$config->load('config');

# Get an item.
$item = $config->get('foo');
```

### Recommended Directory Structure
For the default config providers, you'll need to create a directory structure somewhere in your project to hold your configuration files.

Recommended structure:

```
config/
    config.php
    another_config.php
    /local/
        config.php
    /test/
        config.php
    /prod/
        config.php
```

Default configs go in the root `config` directory. There must also sub-directories for each environment (local/test/prod) if you want to override the default setting when running in different environments.

### Using ArrayProvider
Create a .php file in your config directory that returns an array of config values.

``` php
<?php
return [
	'foo' => 'Foo',
	'bar' => 'Bar'
];
```

Get an instance of the `ArrayProvider` class, passing the path to your config directory to the constructor.

``` php
$provider = new \werx\Config\Provider\ArrayProvider('/path/to/config/directory');
```

### Using JsonProvider
Create a .json file in your config directory that returns an array of config values.

``` json
{
    "foo" : "Foo",
    "bar" : "Bar"
}
```

Get an instance of the `JsonProvider` class, passing the path to your config directory to the constructor.

``` php
$provider = new \werx\Config\Provider\JsonProvider('/path/to/config/directory');
```

### Loading A Configuration Group
In this example, you would be loading the array from `config.php` in your config directory.

```php
$config = new \werx\Config\Container($provider);
$config->load('config');

```

### Get A Configuration Value
```php
$item = $config->get('foo');

print $item;
// Foo
```

### Get A Default Value
If a configuration item doesn't exist, `$config->get()` will return null. You can override the default return value by passing the new default as the 2nd parameter.

```php
$item = $config->get('doesnotexist', false);
var_dump($item);
// false;
```

### Loading Environment-Specific Configuration Group
In this example, you would be loading the array from `config.php` and `test/config.php`. Keys from `test/config.php` will replace keys with the same name from `config.php`.

```php
$config->setEnvironment('test');
$config->load('config');
```

### Loading Multiple Configuration Groups

If you have more than one configuration group to load, you can call `load()` multiple times, or you can pass an array of config groups.

```php
$config->load(['config', 'another_config']);
```

### Avoiding Collisions On Config Property Names

By default, if a configuration property name is found in multiple config files, the config value will be replaced each time that property name is found in a config file. If you prefer, you can tell the loader to index the config container with the name of the config group to prevent name collisions. This is accomplished by passing `true` as the second parameter to `load()`.

```php
$config->load(['config', 'email'], true);
```

Then to retrieve your indexed property name, call the "magic" method named the same as the config file you loaded.

```
// Get the value for the 'host' property from the 'email' configuration group.
$item = $config->email('host', 'smtp.mailgun.org');
```
> As with the `get()` method, the seond parameter above is the default value if the config item doesn't exist.

Or you can return all of the items in the 'email' config group as an array by not passing any parameters.

```
$items = $config->email();
```

## Installation
This is installable and autoloadable via Composer as [werx/config](https://packagist.org/packages/werx/config). If you aren't familiar with the Composer Dependency Manager for PHP, [you should read this first](https://getcomposer.org/doc/00-intro.md).

Example composer.json
``` json
{
	"require": {
		"werx/config": "dev-master"
	}
}
```

## Contributing

### Unit Testing

``` bash
$ vendor/bin/phpunit
```

### Coding Standards
This library uses [PHP_CodeSniffer](http://www.squizlabs.com/php-codesniffer) to ensure coding standards are followed.

I have adopted the [PHP FIG PSR-2 Coding Standard](http://www.php-fig.org/psr/psr-2/) EXCEPT for the tabs vs spaces for indentation rule. PSR-2 says 4 spaces. I use tabs. No discussion.

To support indenting with tabs, I've defined a custom PSR-2 ruleset that extends the standard [PSR-2 ruleset used by PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/CodeSniffer/Standards/PSR2/ruleset.xml). You can find this ruleset in the root of this project at PSR2Tabs.xml

Executing the codesniffer command from the root of this project to run the sniffer using these custom rules.


	$ ./codesniffer
