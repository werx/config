<?php
namespace werx\Config\Providers;

use werx\Config\Providers\ProviderInterface;

use Exception;

class ArrayProvider implements ProviderInterface
{
	public $path;

	public function __construct($path = null)
	{
		if (!empty($path)) {
			$this->setPath($path);
		}
	}

	public function setPath($path)
	{
		$path = rtrim($path, DIRECTORY_SEPARATOR);

		if (!file_exists($path)) {
			throw new Exception("Specified config path doesn't exist: " . $path);
		}

		$this->path = $path;
	}

	public function load($file, $environment = null)
	{
		$path = $this->resolveFilePath($file, $environment);

		if (empty($file)) {
			throw new Exception('No config file specified.');
		} elseif (!file_exists($path)) {
			return [];
		} else {

			$items = require($path);

			if (is_array($items)) {
				return $items;
			} else {
				return [];
			}
		}
	}

	public function resolveFilePath($file, $environment = null)
	{
		if (!empty($environment)) {
			return $this->path . DIRECTORY_SEPARATOR . $environment . DIRECTORY_SEPARATOR . basename($file, '.php') . '.php';
		} else {
			return $this->path . DIRECTORY_SEPARATOR . basename($file, '.php') . '.php';
		}
	}

	protected function singleton(callable $factory)
	{
		$singleton_factory = function ($container) use($factory) {
			static $instance;
			if ($instance === null) {
				$instance = $factory($container);
			}
			return $instance;
		};
		return $singleton_factory;
	}
}
