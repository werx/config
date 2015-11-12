<?php

namespace werx\Config\Providers;

use \werx\Config\Container;

interface ProviderInterface
{
	/**
	 * @param string $group
	 * @param string|null $environment
	 */
	public function load($group, $environment = null);
}
