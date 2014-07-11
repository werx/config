<?php

namespace werx\Config\Providers;

interface ProviderInterface
{
	/**
	 * @string $group
	 * @string null $environment
	 */
	public function load($group, $environment = null);
}
