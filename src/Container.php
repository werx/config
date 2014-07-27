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
	public function load($group = null, $index = false)
	{

		if (is_array($group)) {
			// Loading multiple config groups
			foreach ($group as $g) {
				$this->load($g, $index);
			}
		} else {
			// First, grab any default items.
			$default_items = $this->provider->load($group);

			// Next, grab any environment-specific items.
			$environment_items = $this->provider->load($group, $this->environment);

			// Merge them together. Environment-specific items will replace default items.
			$items = array_merge($default_items, $environment_items);

			// Add this config group to our index.
			foreach ($items as $key => $value) {
				if ($index === true) {
					$this->set($key, $value, $group);
				} else {
					$this->set($key, $value, 'default');
				}
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
		if (array_key_exists($index_name, $this->items) && array_key_exists($key, $this->items[$index_name])) {
			return $this->items[$index_name][$key];
		} else {
			return $default_value;
		}
	}

	public function all()
	{
		return $this->items;
	}

	public function clear()
	{
		return $this->items = [];
	}
}
