<?php
namespace werx\Config\Tests;

use werx\Config\Providers\ArrayProvider;

class ArrayProviderTests extends AbstractProviderTests
{
	public $resource_directory = null;

	public function __construct()
	{
		$this->resource_directory = __DIR__ . DIRECTORY_SEPARATOR . 'resources/config_array';
	}

	/**
	 * @expectedException \Exception
	 */
	public function testInvalidPathShouldThrowException()
	{
		$provider = new ArrayProvider;
		$provider->setPath('/path/to/nowhere');
	}

	public function getProvider()
	{
		$provider = new ArrayProvider($this->resource_directory);
		return $provider;
	}
}
