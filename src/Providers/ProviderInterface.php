<?php

namespace werx\Config\Providers;

interface ProviderInterface
{
	/**
	 * @param string $group
	 * @param string|null $environment
	 */
	public function load($group, $environment = null);
}
