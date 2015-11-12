<?php

namespace werx\Config;

use werx\Config\Providers\ProviderInterface;
use werx\Config\Providers as Providers;
use Exception;

class Container
{
	public $environment;
	public $provider;
	public $items = [];

	public function __construct(ProviderInterface $provider = null, $environment = null)
	{
		$this->setProvider($provider);
		$this->setEnvironment($environment);
	}

	public function setProvider($provider = null)
	{
		if (empty($provider)) {
			$provider = new Providers\ArrayProvider();
		}
		$this->provider = $provider;
	}

	public function setEnvironment($environment = null)
	{
		if (!empty($environment)) {
			$this->environment = $environment;
		}
	}


	/** @noinspection PhpInconsistentReturnPointsInspection */
	public function load($group = null, $index = false, $reload = true)
	{

		if (is_array($group)) {
			// Loading multiple config groups
			foreach ($group as $g) {
				$this->load($g, $index);
			}
		} else {

			if (empty($group)) {
				$group = 'default';
			}
			if ($index===true) {
				$index_group = $group;
			} else {
				$index_group = 'default';
			}
			if (!$reload && array_key_exists($group, $this->items)) {
				return $this->items[$group];
			}

			// First, grab any default items.
			$default_items = $this->provider->load($group);

			// Next, grab any environment-specific items.
			$environment_items = $this->provider->load($group, $this->environment);

			// Merge them together. Environment-specific items will replace default items.
			$items = array_merge($default_items, $environment_items);

			// Add this config group to our index.
			foreach ($items as $key => $value) {
				$this->set($key, $value, $index_group);
			}

			// Return the items loaded in this call, NOT ALL the items in the config array.
			return $items;
		}
	}

	public function set($key, $value, $index_name = 'default')
	{
		$this->items[$index_name][$key] = $value;
	}

	public function get($key, $default_value = null, $index_name = 'default')
	{
		if (array_key_exists($index_name, $this->items)) {
			return $this->evaluate($this->walkConfig($this->items[$index_name], $key, $default_value));
		} else {
			return $this->evaluate($default_value);
		}
	}

	protected function walkConfig($config, $key, $default_value)
	{
		$keys = explode(":", $key);
		$result = $config;
		foreach ($keys as $k) {
			if (!is_array($result) || !array_key_exists($k, $result)) {
				return $default_value;
			}
			$data = $result[$k];
			if (is_string($data) && substr($data, 0, 1) === "#") {
				if (strpos($data, ":") > 0) {
					$group = strstr(substr($data,1),":", true);
					$this->load($group, true, false);
					$data = $this->$group( substr(strstr($data,":"),1), $default_value);
				} else {
					$data = $this->load(substr($data,1), true, false);
				}
			}
			$result = $data;
		}
		return $data;
	}

	public function all($index = null)
	{
		if (empty($index)) {
			return $this->items;
		} elseif (array_key_exists($index, $this->items) && is_array($this->items[$index])) {
			return $this->items[$index];
		} else {
			return [];
		}
	}

	public function clear()
	{
		return $this->items = [];
	}

	public function __call($method, $args) {

		switch(count($args)) {
			case 2:
				$item = $args[0];
				$default = $args[1];
				break;
			case 1:
				$item = $args[0];
				$default = null;
				break;
			case 0:
				return $this->all($method);
			default:
				throw new \Exception('Invalid Argument Count');
		}

		if (array_key_exists($method, $this->items)) {
			return $this->get($item, $default, $method);
		}
	}

	private function evaluate($value)
	{
		$invokable = is_object($value) && method_exists($value,'__invoke') || is_callable($value);
		return $invokable ? $value($this) : $value;
	}
}
