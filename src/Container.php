<?php

namespace werx\Config;

use werx\Config\Providers\ProviderInterface;
use werx\Config\Providers as Providers;

/**
 * Class Container
 * @package werx\Config
 */
class Container implements \ArrayAccess
{

	public $environment;

	/**
	 * @var ProviderInterface
	 */
	public $provider;

	/**
	 * @var array
	 */
	public $items = [];


	/**
	 * @param ProviderInterface|null $provider
	 * @param null $environment
	 */
	public function __construct(ProviderInterface $provider = null, $environment = null)
	{
		$this->setProvider($provider);
		$this->setEnvironment($environment);
	}

	/**
	 * @param ProviderInterface|null $provider
	 */
	public function setProvider(ProviderInterface $provider = null)
	{
		if (empty($provider)) {
			$provider = new Providers\ArrayProvider();
		}
		$this->provider = $provider;
	}

	/**
	 * @param null $environment
	 */
	public function setEnvironment($environment = null)
	{
		if (!empty($environment)) {
			$this->environment = $environment;
		}
	}

	/**
	 * @param null $group
	 * @param bool|false $index
	 * @param bool|true $reload
	 * @return array
	 *
	 * @noinspection PhpInconsistentReturnPointsInspection
	 */
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
			if ($index === true) {
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
			$items = self::array_merge_deep($default_items, $environment_items);

			// Add this config group to our index.
			foreach ($items as $key => $value) {
				$this->set($key, $value, $index_group);
			}

			// Return the items loaded in this call, NOT ALL the items in the config array.
			return $items;
		}
	}

	/**
	 * Add an item to the container.
	 *
	 * @param $key
	 * @param $value
	 * @param null $index_name
	 */
	public function set($key, $value, $index_name = null)
	{
		list($index_name, $key) = $this->normalizeAccessKey($key, $index_name);

		$this->items[$index_name][$key] = $value;
	}

	/**
	 * Alias for set()
	 *
	 * @param $key
	 * @param $value
	 * @param null $index_name
	 */
	public function add($key, $value, $index_name = null)
	{
		$this->set($key, $value, $index_name);
	}

	/**
	 * Allows compound keys to get to indexed config groups.
	 *
	 * @param $key
	 * @param null $index_name
	 * @return array
	 */
	protected function normalizeAccessKey($key, $index_name = null)
	{
		if (empty($index_name)) {
			@list($index_name, $key) = explode('.', $key);

			if (empty($key)) {
				$key = $index_name;
				$index_name = 'default';
			}
		}

		return [$index_name, $key];
	}

	/**
	 * @param $key
	 * @param null $index_name
	 * @return bool
	 */
	public function has($key, $index_name = null)
	{

		list($index_name, $key) = $this->normalizeAccessKey($key, $index_name);

		if (array_key_exists($index_name, $this->items)) {
			if (array_key_exists($key, $this->items[$index_name])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove an item from the container.
	 *
	 * @param $key
	 * @param null $index_name
	 */
	public function forget($key, $index_name = null)
	{
		list($index_name, $key) = $this->normalizeAccessKey($key, $index_name);

		if ($this->has($key, $index_name)) {
			unset($this->items[$index_name][$key]);
		}
	}

	/**
	 * Get an item from the container.
	 *
	 * @param $key
	 * @param null $default_value
	 * @param null $index_name
	 * @return mixed
	 */
	public function get($key, $default_value = null, $index_name = null)
	{

		list($index_name_x, $key) = $this->normalizeAccessKey($key, $index_name);

		if (!empty($index_name_x)) {
			$index_name = $index_name_x;
		}

		if (array_key_exists($index_name, $this->items)) {
			return $this->evaluate($this->walkConfig($this->items[$index_name], $key, $default_value));
		} else {
			return $this->evaluate($default_value);
		}
	}

	/**
	 * @param $config
	 * @param $key
	 * @param $default_value
	 * @return array|null
	 */
	protected function walkConfig($config, $key, $default_value)
	{
		$keys = explode(":", $key);
		$result = $config;
		$data = null;

		foreach ($keys as $k) {
			if (!is_array($result) || !array_key_exists($k, $result)) {
				return $default_value;
			}
			$data = $result[$k];
			if (is_string($data) && substr($data, 0, 1) === "#") {
				if (strpos($data, ":") > 0) {
					$group = strstr(substr($data, 1), ":", true);
					$this->load($group, true, false);
					$data = $this->$group(substr(strstr($data, ":"), 1), $default_value);
				} else {
					$data = $this->load(substr($data, 1), true, false);
				}
			}
			$result = $data;
		}

		return $data;
	}

	/**
	 * Get all items from the container.
	 *
	 * @param null $index
	 * @return array
	 */
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

	/**
	 * Clear the container of all items.
	 *
	 * @return array
	 */
	public function clear()
	{
		return $this->items = [];
	}

	/**
	 * Magic method to allow use to call indexed config groups.
	 *
	 * Example: $config->database('dsn');
	 *
	 * @param $method
	 * @param $args
	 * @return array|mixed
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $args)
	{
		switch (count($args)) {
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
				throw new \BadMethodCallException('Invalid Argument Count');
		}

		return $this->get($item, $default, $method);
	}

	/**
	 * Recursively perform an array merge, preserving keys and overriding dupes.
	 *
	 * Credit goes to this SO post: http://stackoverflow.com/questions/25712099/php-multidimensional-array-merge-recursive
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 */
	public static function array_merge_deep(array $array1, array $array2)
	{
		$merged = $array1;

		foreach ($array2 as $key => $value) {
			if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
				$merged[$key] = self::array_merge_deep($merged[$key], $value);
			} elseif (is_numeric($key)) {
				// Numeric key. Look at values instead of keys to determine if it already exists.
				if (!in_array($value, $merged)) {
					$merged[] = $value;
				}
			} else {
				$merged[$key] = $value;
			}

		}

		return $merged;
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	private function evaluate($value)
	{
		$invokable = is_object($value) && method_exists($value, '__invoke') || is_callable($value);

		return $invokable ? $value($this) : $value;
	}

	/**
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	public function offsetExists($offset)
	{
		return $this->has($offset);
	}

	/**
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	public function offsetGet($offset)
	{
		list($index_name, $key) = $this->normalizeAccessKey($offset);

		return $this->get($key, null, $index_name);
	}

	/**
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetSet($offset, $value)
	{
		list($index_name, $key) = $this->normalizeAccessKey($offset);

		$this->set($key, $value, $index_name);
	}

	/**
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetUnset($offset)
	{
		$this->forget($offset);
	}
}
